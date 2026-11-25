<?php

declare(strict_types=1);

/*
 * Alertas manuais: criados pelo cliente (ou pela equipe) e recebidos
 * pelos administradores/funcionários. Ficam salvos na tabela "alertas".
 *
 * Quem vê cada alerta:
 *   - adm ........... todos os alertas
 *   - funcionário ... os enviados para ele, para "toda a equipe" ou criados por ele
 *   - cliente ....... os que ele criou e os ligados aos sistemas dele
 */

const PRIORIDADES_ALERTA = ['alta' => 'Alta', 'media' => 'Média', 'baixa' => 'Baixa'];

/** Cria a tabela "alertas" se ainda não existir (bancos criados antes desta versão). */
function garantirTabelaAlertas(PDO $pdo): void
{
    static $pronta = false;

    if ($pronta) {
        return;
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS alertas (
            ID_ALERTA INT PRIMARY KEY AUTO_INCREMENT,
            ID_AUTOR INT NOT NULL,
            ID_DESTINATARIO INT NULL,
            ID_fotovoltaicos INT NULL,
            titulo VARCHAR(100) NOT NULL,
            descricao TEXT NOT NULL,
            prioridade ENUM('alta', 'media', 'baixa') NOT NULL DEFAULT 'media',
            situacao ENUM('pendente', 'resolvido') NOT NULL DEFAULT 'pendente',
            resposta TEXT NULL,
            ID_RESPONDIDO_POR INT NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME NULL,
            FOREIGN KEY (ID_AUTOR) REFERENCES usuarios (ID_USUARIO) ON DELETE CASCADE,
            FOREIGN KEY (ID_DESTINATARIO) REFERENCES usuarios (ID_USUARIO) ON DELETE SET NULL,
            FOREIGN KEY (ID_fotovoltaicos) REFERENCES fotovoltaicos (ID_fotovoltaicos) ON DELETE SET NULL,
            FOREIGN KEY (ID_RESPONDIDO_POR) REFERENCES usuarios (ID_USUARIO) ON DELETE SET NULL
        )
    ");

    $pronta = true;
}

/* ------------------------------------------------------------------ */
/* Proteção dos formulários (CSRF)                                    */
/* ------------------------------------------------------------------ */

/** Código secreto da sessão enviado junto com os formulários. */
function tokenCsrf(): string
{
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function validarCsrf(mixed $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf'])
        && is_string($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}

/* ------------------------------------------------------------------ */
/* Consultas                                                          */
/* ------------------------------------------------------------------ */

/** Administradores e funcionários (quem pode receber alertas). */
function listarEquipe(PDO $pdo): array
{
    return $pdo->query("
        SELECT ID_USUARIO, nome, TIPO
        FROM usuarios
        WHERE TIPO IN ('adm', 'funcionario')
        ORDER BY TIPO = 'adm' DESC, nome
    ")->fetchAll();
}

/** Parte do WHERE que limita os alertas que o usuário pode ver. */
function filtroAlertasVisiveis(array $usuario): array
{
    $id = (int) $usuario['ID_USUARIO'];
    $tipo = $usuario['TIPO'] ?: 'cliente';

    if ($tipo === 'adm') {
        return ['1 = 1', []];
    }

    if ($tipo === 'funcionario') {
        return ['(a.ID_DESTINATARIO IS NULL OR a.ID_DESTINATARIO = ? OR a.ID_AUTOR = ?)', [$id, $id]];
    }

    return ['(a.ID_AUTOR = ? OR f.ID_USUARIO = ?)', [$id, $id]];
}

/** Alertas manuais visíveis para o usuário, no mesmo formato dos automáticos. */
function listarAlertasManuais(PDO $pdo, array $usuario): array
{
    garantirTabelaAlertas($pdo);
    [$filtro, $params] = filtroAlertasVisiveis($usuario);

    $stmt = $pdo->prepare("
        SELECT a.*,
               autor.nome AS autor_nome, autor.TIPO AS autor_tipo,
               dest.nome AS destinatario_nome,
               resp.nome AS respondido_por_nome,
               f.modelo_painel, dono.nome AS dono_nome
        FROM alertas a
        INNER JOIN usuarios autor ON autor.ID_USUARIO = a.ID_AUTOR
        LEFT JOIN usuarios dest ON dest.ID_USUARIO = a.ID_DESTINATARIO
        LEFT JOIN usuarios resp ON resp.ID_USUARIO = a.ID_RESPONDIDO_POR
        LEFT JOIN fotovoltaicos f ON f.ID_fotovoltaicos = a.ID_fotovoltaicos
        LEFT JOIN usuarios dono ON dono.ID_USUARIO = f.ID_USUARIO
        WHERE {$filtro}
        ORDER BY a.criado_em DESC, a.ID_ALERTA DESC
    ");
    $stmt->execute($params);

    $alertas = [];

    foreach ($stmt->fetchAll() as $a) {
        $autorEhCliente = ($a['autor_tipo'] ?: 'cliente') === 'cliente';

        $alertas[] = [
            'manual' => true,
            'id' => (int) $a['ID_ALERTA'],
            'id_autor' => (int) $a['ID_AUTOR'],
            'titulo' => (string) $a['titulo'],
            'descricao' => (string) $a['descricao'],
            'prioridade' => (string) $a['prioridade'],
            'situacao' => (string) $a['situacao'],
            'cliente' => $a['dono_nome'] ?? ($autorEhCliente ? $a['autor_nome'] : '—'),
            'sistema' => $a['ID_fotovoltaicos'] !== null
                ? '#' . $a['ID_fotovoltaicos'] . ' ' . $a['modelo_painel']
                : 'Sem sistema específico',
            'data' => substr((string) $a['criado_em'], 0, 10),
            'autor' => (string) $a['autor_nome'],
            'destinatario' => $a['destinatario_nome'] ?? 'Toda a equipe',
            'resposta' => $a['resposta'],
            'respondido_por' => $a['respondido_por_nome'],
            'criado_em' => (string) $a['criado_em'],
            'atualizado_em' => $a['atualizado_em'],
        ];
    }

    return $alertas;
}

/** Busca um alerta só se o usuário puder vê-lo. */
function buscarAlertaVisivel(PDO $pdo, int $idAlerta, array $usuario): array|false
{
    garantirTabelaAlertas($pdo);
    [$filtro, $params] = filtroAlertasVisiveis($usuario);

    $stmt = $pdo->prepare("
        SELECT a.*
        FROM alertas a
        LEFT JOIN fotovoltaicos f ON f.ID_fotovoltaicos = a.ID_fotovoltaicos
        WHERE a.ID_ALERTA = ? AND {$filtro}
    ");
    $stmt->execute([$idAlerta, ...$params]);

    return $stmt->fetch();
}

/** A equipe responde/resolve; o cliente pode mexer apenas nos alertas que ele criou. */
function podeAlterarAlerta(array $alerta, array $usuario): bool
{
    return ehEquipe($usuario) || (int) $alerta['ID_AUTOR'] === (int) $usuario['ID_USUARIO'];
}

/** Quantidade de caracteres (funciona com acentos, sem precisar de mbstring). */
function contarCaracteres(string $texto): int
{
    return (int) preg_match_all('/./us', $texto);
}

/* ------------------------------------------------------------------ */
/* Ações                                                              */
/* ------------------------------------------------------------------ */

/** Salva um novo alerta. Lança InvalidArgumentException com a mensagem para o usuário. */
function criarAlerta(PDO $pdo, array $usuario, array $dados): int
{
    garantirTabelaAlertas($pdo);

    $titulo = trim((string) ($dados['titulo'] ?? ''));
    $descricao = trim((string) ($dados['descricao'] ?? ''));
    $prioridade = (string) ($dados['prioridade'] ?? 'media');
    $idSistema = (int) ($dados['ID_fotovoltaicos'] ?? 0);
    $idDestinatario = (int) ($dados['ID_DESTINATARIO'] ?? 0);

    if ($titulo === '' || $descricao === '') {
        throw new InvalidArgumentException('Preencha o título e a descrição do alerta.');
    }
    if (contarCaracteres($titulo) > 100) {
        throw new InvalidArgumentException('O título pode ter no máximo 100 caracteres.');
    }
    if (contarCaracteres($descricao) > 1000) {
        throw new InvalidArgumentException('A descrição pode ter no máximo 1000 caracteres.');
    }
    if (!isset(PRIORIDADES_ALERTA[$prioridade])) {
        throw new InvalidArgumentException('Escolha uma prioridade da lista.');
    }

    // O cliente só pode escolher sistemas dele
    if ($idSistema > 0 && !podeAcessarSistema($pdo, $idSistema, $usuario)) {
        throw new InvalidArgumentException('Escolha um dos seus sistemas.');
    }

    // O destinatário precisa ser alguém da equipe
    if ($idDestinatario > 0) {
        $destinatario = read(
            $pdo,
            'usuarios',
            "ID_USUARIO = ? AND TIPO IN ('adm', 'funcionario')",
            [$idDestinatario]
        );

        if (!$destinatario) {
            throw new InvalidArgumentException('Escolha um destinatário da equipe.');
        }
    }

    return (int) create($pdo, 'alertas', [
        'ID_AUTOR' => (int) $usuario['ID_USUARIO'],
        'ID_DESTINATARIO' => $idDestinatario > 0 ? $idDestinatario : null,
        'ID_fotovoltaicos' => $idSistema > 0 ? $idSistema : null,
        'titulo' => $titulo,
        'descricao' => $descricao,
        'prioridade' => $prioridade,
    ]);
}

/** $acao: "responder" (só salva o recado), "resolver" ou "reabrir". */
function atualizarAlerta(PDO $pdo, array $usuario, int $idAlerta, string $acao, string $resposta): void
{
    $alerta = buscarAlertaVisivel($pdo, $idAlerta, $usuario);

    if (!$alerta || !podeAlterarAlerta($alerta, $usuario)) {
        throw new InvalidArgumentException('Você não pode alterar este alerta.');
    }

    $resposta = trim($resposta);

    if (contarCaracteres($resposta) > 1000) {
        throw new InvalidArgumentException('O recado pode ter no máximo 1000 caracteres.');
    }

    $dados = ['atualizado_em' => date('Y-m-d H:i:s')];

    if ($resposta !== '') {
        $dados['resposta'] = $resposta;
        $dados['ID_RESPONDIDO_POR'] = (int) $usuario['ID_USUARIO'];
    }

    if ($acao === 'resolver') {
        $dados['situacao'] = 'resolvido';
    } elseif ($acao === 'reabrir') {
        $dados['situacao'] = 'pendente';
    } elseif ($acao === 'responder') {
        if ($resposta === '') {
            throw new InvalidArgumentException('Escreva o recado antes de salvar.');
        }
    } else {
        throw new InvalidArgumentException('Ação inválida.');
    }

    update($pdo, 'alertas', $dados, 'ID_ALERTA = ?', [$idAlerta]);
}

/** Número do menu: alertas automáticos + manuais pendentes. */
function contarAlertasMenu(PDO $pdo, array $usuario): int
{
    $total = contarAlertasPendentes($pdo, escopoUsuario($usuario));

    try {
        foreach (listarAlertasManuais($pdo, $usuario) as $a) {
            if ($a['situacao'] === 'pendente') {
                $total++;
            }
        }
    } catch (Throwable $e) {
        error_log('Erro ao contar alertas manuais: ' . $e->getMessage());
    }

    return $total;
}
