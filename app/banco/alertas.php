<?php

declare(strict_types=1);

const PRIORIDADES_ALERTA = [
    'alta' => 'Alta',
    'media' => 'Média',
    'baixa' => 'Baixa',
];

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
            FOREIGN KEY (ID_AUTOR)
                REFERENCES usuarios (ID_USUARIO) ON DELETE CASCADE,
            FOREIGN KEY (ID_DESTINATARIO)
                REFERENCES usuarios (ID_USUARIO) ON DELETE SET NULL,
            FOREIGN KEY (ID_fotovoltaicos)
                REFERENCES fotovoltaicos (ID_fotovoltaicos) ON DELETE SET NULL,
            FOREIGN KEY (ID_RESPONDIDO_POR)
                REFERENCES usuarios (ID_USUARIO) ON DELETE SET NULL
        )
    ");

    $pronta = true;
}

function garantirTabelaResolucoesAutomaticas(PDO $pdo): void
{
    static $prontas = [];

    $conexao = spl_object_id($pdo);

    if (isset($prontas[$conexao])) {
        return;
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS alertas_automaticos_resolvidos (
            ID_USUARIO INT NOT NULL,
            chave CHAR(64) NOT NULL,
            resolvido_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (ID_USUARIO, chave),
            FOREIGN KEY (ID_USUARIO)
                REFERENCES usuarios (ID_USUARIO) ON DELETE CASCADE
        )
    ");

    $prontas[$conexao] = true;
}

function chaveAlertaAutomatico(array $alerta): string
{
    return hash('sha256', serialize([
        (string) $alerta['sistema'],
        (string) $alerta['titulo'],
        (string) $alerta['descricao'],
        $alerta['data'] ?? null,
    ]));
}

function listarAlertasAutomaticosUsuario(PDO $pdo, array $usuario): array
{
    garantirTabelaResolucoesAutomaticas($pdo);

    $idUsuario = escopoUsuario($usuario);

    $stmt = $pdo->prepare("
        SELECT chave, resolvido_em
        FROM alertas_automaticos_resolvidos
        WHERE ID_USUARIO = ?
    ");
    $stmt->execute([$idUsuario]);

    $resolucoes = [];

    foreach ($stmt->fetchAll() as $resolucao) {
        $resolucoes[$resolucao['chave']] = $resolucao['resolvido_em'];
    }

    $alertas = gerarAlertas($pdo, $idUsuario);

    foreach ($alertas as &$alerta) {
        // Manutenções realizadas são histórico, não notificações reabríveis.
        $alerta['gerenciavel'] = $alerta['situacao'] === 'pendente';
        $alerta['resolvido_em'] = null;

        if (!$alerta['gerenciavel']) {
            continue;
        }

        $chave = chaveAlertaAutomatico($alerta);
        $alerta['chave_automatico'] = $chave;

        if (isset($resolucoes[$chave])) {
            $alerta['situacao'] = 'resolvido';
            $alerta['resolvido_em'] = $resolucoes[$chave];
        }
    }

    unset($alerta);

    return $alertas;
}

function atualizarAlertaAutomatico(
    PDO $pdo,
    array $usuario,
    string $chave,
    string $acao
): void {
    if (
        !in_array($acao, ['resolver_automatico', 'reabrir_automatico'], true)
        || !preg_match('/^[a-f0-9]{64}$/D', $chave)
    ) {
        throw new InvalidArgumentException('Ação ou alerta inválido.');
    }

    $idUsuario = escopoUsuario($usuario);
    $visivel = false;

    // Valida o alerta usando somente os sistemas da conta autenticada.
    foreach (gerarAlertas($pdo, $idUsuario) as $alerta) {
        if (
            $alerta['situacao'] === 'pendente'
            && hash_equals(chaveAlertaAutomatico($alerta), $chave)
        ) {
            $visivel = true;
            break;
        }
    }

    if (!$visivel) {
        throw new InvalidArgumentException(
            'Este alerta mudou ou não está mais disponível para sua conta. Recarregue a página.'
        );
    }

    garantirTabelaResolucoesAutomaticas($pdo);

    if ($acao === 'resolver_automatico') {
        $stmt = $pdo->prepare("
            INSERT INTO alertas_automaticos_resolvidos (ID_USUARIO, chave)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE resolvido_em = CURRENT_TIMESTAMP
        ");
    } else {
        $stmt = $pdo->prepare("
            DELETE FROM alertas_automaticos_resolvidos
            WHERE ID_USUARIO = ? AND chave = ?
        ");
    }

    $stmt->execute([$idUsuario, $chave]);
}

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

function listarEquipe(PDO $pdo): array
{
    return $pdo->query("
        SELECT ID_USUARIO, nome, TIPO
        FROM usuarios
        WHERE TIPO IN ('adm', 'funcionario')
        ORDER BY TIPO = 'adm' DESC, nome
    ")->fetchAll();
}

function filtroAlertasVisiveis(array $usuario): array
{
    $id = escopoUsuario($usuario);

    if (ehEquipe($usuario)) {
        return [
            '(a.ID_AUTOR = ? OR f.ID_USUARIO = ? OR a.ID_DESTINATARIO = ? OR a.ID_DESTINATARIO IS NULL)',
            [$id, $id, $id],
        ];
    }

    return [
        '(a.ID_AUTOR = ? OR f.ID_USUARIO = ?)',
        [$id, $id],
    ];
}

function listarAlertasManuais(PDO $pdo, array $usuario): array
{
    garantirTabelaAlertas($pdo);

    [$filtro, $params] = filtroAlertasVisiveis($usuario);

    $stmt = $pdo->prepare("
        SELECT a.*,
               autor.nome AS autor_nome,
               autor.TIPO AS autor_tipo,
               dest.nome AS destinatario_nome,
               resp.nome AS respondido_por_nome,
               f.modelo_painel,
               dono.nome AS dono_nome
        FROM alertas a
        INNER JOIN usuarios autor
            ON autor.ID_USUARIO = a.ID_AUTOR
        LEFT JOIN usuarios dest
            ON dest.ID_USUARIO = a.ID_DESTINATARIO
        LEFT JOIN usuarios resp
            ON resp.ID_USUARIO = a.ID_RESPONDIDO_POR
        LEFT JOIN fotovoltaicos f
            ON f.ID_fotovoltaicos = a.ID_fotovoltaicos
        LEFT JOIN usuarios dono
            ON dono.ID_USUARIO = f.ID_USUARIO
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
            'cliente' => $a['dono_nome']
                ?? ($autorEhCliente ? $a['autor_nome'] : '—'),
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

function buscarAlertaVisivel(
    PDO $pdo,
    int $idAlerta,
    array $usuario
): array|false {
    garantirTabelaAlertas($pdo);

    [$filtro, $params] = filtroAlertasVisiveis($usuario);

    $stmt = $pdo->prepare("
        SELECT a.*
        FROM alertas a
        LEFT JOIN fotovoltaicos f
            ON f.ID_fotovoltaicos = a.ID_fotovoltaicos
        WHERE a.ID_ALERTA = ? AND {$filtro}
    ");
    $stmt->execute([$idAlerta, ...$params]);

    return $stmt->fetch();
}

function podeAlterarAlerta(array $alerta, array $usuario): bool
{
    return ehEquipe($usuario)
        || (int) $alerta['ID_AUTOR'] === (int) $usuario['ID_USUARIO'];
}

function contarCaracteres(string $texto): int
{
    return (int) preg_match_all('/./us', $texto);
}

function criarAlerta(PDO $pdo, array $usuario, array $dados): int
{
    garantirTabelaAlertas($pdo);

    $titulo = trim((string) ($dados['titulo'] ?? ''));
    $descricao = trim((string) ($dados['descricao'] ?? ''));
    $prioridade = (string) ($dados['prioridade'] ?? 'media');
    $idSistema = (int) ($dados['ID_fotovoltaicos'] ?? 0);
    $idDestinatario = (int) ($dados['ID_DESTINATARIO'] ?? 0);

    if ($titulo === '' || $descricao === '') {
        throw new InvalidArgumentException(
            'Preencha o título e a descrição do alerta.'
        );
    }

    if (contarCaracteres($titulo) > 100) {
        throw new InvalidArgumentException(
            'O título pode ter no máximo 100 caracteres.'
        );
    }

    if (contarCaracteres($descricao) > 1000) {
        throw new InvalidArgumentException(
            'A descrição pode ter no máximo 1000 caracteres.'
        );
    }

    if (!isset(PRIORIDADES_ALERTA[$prioridade])) {
        throw new InvalidArgumentException(
            'Escolha uma prioridade da lista.'
        );
    }

    if (
        $idSistema > 0
        && !podeAcessarSistema($pdo, $idSistema, $usuario)
    ) {
        throw new InvalidArgumentException(
            'Escolha um dos seus sistemas.'
        );
    }

    if ($idDestinatario > 0) {
        $destinatario = read(
            $pdo,
            'usuarios',
            "ID_USUARIO = ? AND TIPO IN ('adm', 'funcionario')",
            [$idDestinatario]
        );

        if (!$destinatario) {
            throw new InvalidArgumentException(
                'Escolha um destinatário da equipe.'
            );
        }
    }

    return (int) create($pdo, 'alertas', [
        'ID_AUTOR' => (int) $usuario['ID_USUARIO'],
        'ID_DESTINATARIO' => $idDestinatario > 0
            ? $idDestinatario
            : null,
        'ID_fotovoltaicos' => $idSistema > 0
            ? $idSistema
            : null,
        'titulo' => $titulo,
        'descricao' => $descricao,
        'prioridade' => $prioridade,
    ]);
}

function atualizarAlerta(
    PDO $pdo,
    array $usuario,
    int $idAlerta,
    string $acao,
    string $resposta
): void {
    $alerta = buscarAlertaVisivel($pdo, $idAlerta, $usuario);

    if (!$alerta || !podeAlterarAlerta($alerta, $usuario)) {
        throw new InvalidArgumentException(
            'Você não pode alterar este alerta.'
        );
    }

    $resposta = trim($resposta);

    if (contarCaracteres($resposta) > 1000) {
        throw new InvalidArgumentException(
            'O recado pode ter no máximo 1000 caracteres.'
        );
    }

    $dados = [
        'atualizado_em' => date('Y-m-d H:i:s'),
    ];

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
            throw new InvalidArgumentException(
                'Escreva o recado antes de salvar.'
            );
        }
    } else {
        throw new InvalidArgumentException('Ação inválida.');
    }

    update(
        $pdo,
        'alertas',
        $dados,
        'ID_ALERTA = ?',
        [$idAlerta]
    );
}

// Fonte única para a tela de alertas e o contador do menu.
function resumoAlertasUsuario(PDO $pdo, array $usuario): array
{
    static $cache = [];

    $id = escopoUsuario($usuario);
    $chave = spl_object_id($pdo)
        . ':' . $id
        . ':' . ($usuario['TIPO'] ?? 'cliente');

    if (isset($cache[$chave])) {
        return $cache[$chave];
    }

    $automaticos = listarAlertasAutomaticosUsuario($pdo, $usuario);
    $manuais = listarAlertasManuais($pdo, $usuario);
    $todos = array_merge($manuais, $automaticos);

    $ordem = [
        'alta' => 0,
        'media' => 1,
        'baixa' => 2,
    ];

    usort(
        $todos,
        static fn($a, $b) =>
            [
                $a['situacao'] === 'resolvido',
                $ordem[$a['prioridade']] ?? 3,
                empty($a['manual']),
            ]
            <=>
            [
                $b['situacao'] === 'resolvido',
                $ordem[$b['prioridade']] ?? 3,
                empty($b['manual']),
            ]
    );

    return $cache[$chave] = [
        'todos' => $todos,
        'manuais' => $manuais,
        'pendentes' => array_values(array_filter(
            $todos,
            static fn($a) => $a['situacao'] === 'pendente'
        )),
        'resolvidos' => array_values(array_filter(
            $todos,
            static fn($a) => $a['situacao'] === 'resolvido'
        )),
    ];
}

function contarAlertasMenu(PDO $pdo, array $usuario): int
{
    return count(
        resumoAlertasUsuario($pdo, $usuario)['pendentes']
    );
}
