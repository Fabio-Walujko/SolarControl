<?php

declare(strict_types=1);

/*
 * Consultas usadas pelas páginas do painel.
 * Regra geral: o parâmetro $idUsuario filtra tudo pelo dono do sistema.
 * Quando ele é null (somente para funcionários/administradores), a consulta
 * considera todos os clientes.
 */

/** Tarifa usada para calcular a economia (R$/kWh). Pode ser trocada pela variável TARIFA_KWH. */
define('TARIFA_KWH', (float) (getenv('TARIFA_KWH') ?: 0.92));

const NOMES_MESES = [
    1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun',
    7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez',
];

/* ------------------------------------------------------------------ */
/* Usuários                                                           */
/* ------------------------------------------------------------------ */

/** Dados do usuário sem a senha, com cidade/estado do endereço. */
function buscarUsuario(PDO $pdo, int $idUsuario): array|false
{
    $sql = '
        SELECT u.ID_USUARIO, u.nome, u.email, u.TIPO, u.id_endereco,
               e.cidade, e.estado
        FROM usuarios u
        LEFT JOIN enderecos e ON e.ID_ENDERECO = u.id_endereco
        WHERE u.ID_USUARIO = ?
    ';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idUsuario]);

    return $stmt->fetch();
}

function buscarUsuarioPorEmail(PDO $pdo, string $email): array|false
{
    $stmt = $pdo->prepare('SELECT ID_USUARIO FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $linha = $stmt->fetch();

    return $linha ? buscarUsuario($pdo, (int) $linha['ID_USUARIO']) : false;
}

/** Lista de usuários para os seletores da equipe. */
function listarUsuarios(PDO $pdo): array
{
    return $pdo->query('SELECT ID_USUARIO, nome, TIPO FROM usuarios ORDER BY nome')->fetchAll();
}

/* ------------------------------------------------------------------ */
/* Sistemas fotovoltaicos                                             */
/* ------------------------------------------------------------------ */

/** Converte as variações de status ("Manutenção", "Em manutenção"...) para um valor só. */
function normalizarStatus(?string $status): string
{
    $status = (string) $status;

    if (stripos($status, 'manuten') !== false) {
        return 'Manutencao';
    }
    if (stripos($status, 'inativ') !== false) {
        return 'Inativo';
    }
    if (stripos($status, 'ativ') !== false) {
        return 'Ativo';
    }

    return $status;
}

/**
 * Sistemas com resumo de registros e manutenções.
 * $idUsuario = null traz todos os sistemas (uso da equipe).
 */
function listarSistemas(PDO $pdo, ?int $idUsuario): array
{
    $hoje = date('Y-m-d');

    $sql = '
        SELECT
            f.ID_fotovoltaicos, f.ID_USUARIO, f.modelo_painel,
            f.data_intalacao, f.status_sistema,
            u.nome AS cliente,
            (SELECT COUNT(*) FROM registros r
              WHERE r.ID_fotovoltaicos = f.ID_fotovoltaicos) AS total_registros,
            (SELECT MAX(r.data_registro) FROM registros r
              WHERE r.ID_fotovoltaicos = f.ID_fotovoltaicos) AS ultimo_registro,
            (SELECT MAX(m.data_agendada) FROM manutencao_e_instalacao m
              WHERE m.ID_SISTEMAS = f.ID_fotovoltaicos AND m.data_agendada <= ?) AS ultima_manutencao,
            (SELECT MIN(m.data_agendada) FROM manutencao_e_instalacao m
              WHERE m.ID_SISTEMAS = f.ID_fotovoltaicos AND m.data_agendada > ?) AS proxima_agendada,
            (SELECT MAX(m.previsao_da_proxima_manutencao) FROM manutencao_e_instalacao m
              WHERE m.ID_SISTEMAS = f.ID_fotovoltaicos) AS previsao_manutencao,
            (SELECT m.qualidade_sistema FROM manutencao_e_instalacao m
              WHERE m.ID_SISTEMAS = f.ID_fotovoltaicos AND m.data_agendada <= ?
              ORDER BY m.data_agendada DESC, m.ID_MANUTENCAO DESC LIMIT 1) AS qualidade_sistema
        FROM fotovoltaicos f
        LEFT JOIN usuarios u ON u.ID_USUARIO = f.ID_USUARIO
    ';
    $params = [$hoje, $hoje, $hoje];

    if ($idUsuario !== null) {
        $sql .= ' WHERE f.ID_USUARIO = ?';
        $params[] = $idUsuario;
    }

    $sql .= ' ORDER BY u.nome, f.ID_fotovoltaicos';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/** Confere se o usuário pode mexer no sistema (dono ou equipe). */
function podeAcessarSistema(PDO $pdo, int $idSistema, array $usuario): bool
{
    $sistema = read($pdo, 'fotovoltaicos', 'ID_fotovoltaicos = ?', [$idSistema]);

    if (!$sistema) {
        return false;
    }

    return ehEquipe($usuario) || (int) $sistema['ID_USUARIO'] === (int) $usuario['ID_USUARIO'];
}

/**
 * Estima a potência a partir do texto do modelo.
 * Ex.: "3x Canadian Solar 550W" => 3 módulos de 550 W = 1,65 kWp.
 */
function estimarPotencia(array $sistemas): array
{
    $kwp = 0.0;
    $modulos = 0;
    $semPotencia = 0;

    foreach ($sistemas as $s) {
        $modelo = (string) $s['modelo_painel'];
        $quantidade = preg_match('/^\s*(\d+)\s*x\b/i', $modelo, $q) ? (int) $q[1] : 1;
        $modulos += $quantidade;

        if (preg_match('/(\d+(?:[.,]\d+)?)\s*Wp?\b/i', $modelo, $w)) {
            $kwp += $quantidade * (float) str_replace(',', '.', $w[1]) / 1000;
        } else {
            $semPotencia++;
        }
    }

    return ['kwp' => $kwp, 'modulos' => $modulos, 'sem_potencia' => $semPotencia];
}

/* ------------------------------------------------------------------ */
/* Registros de energia                                               */
/* ------------------------------------------------------------------ */

/** Soma geração e consumo entre duas datas (inclusive). */
function totaisPeriodo(PDO $pdo, ?int $idUsuario, string $inicio, string $fim): array
{
    $sql = '
        SELECT COALESCE(SUM(r.energia_gerada), 0) AS gerada,
               COALESCE(SUM(r.energia_consumida), 0) AS consumida,
               COUNT(r.ID_REGISTRO) AS lancamentos
        FROM registros r
        INNER JOIN fotovoltaicos f ON f.ID_fotovoltaicos = r.ID_fotovoltaicos
        WHERE r.data_registro BETWEEN ? AND ?
    ';
    $params = [$inicio, $fim];

    if ($idUsuario !== null) {
        $sql .= ' AND f.ID_USUARIO = ?';
        $params[] = $idUsuario;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $linha = $stmt->fetch();

    return [
        'gerada' => (int) $linha['gerada'],
        'consumida' => (int) $linha['consumida'],
        'lancamentos' => (int) $linha['lancamentos'],
    ];
}

/** Totais por mês (AAAA-MM => valores) entre duas datas. */
function totaisPorMes(PDO $pdo, ?int $idUsuario, string $inicio, string $fim): array
{
    $sql = "
        SELECT DATE_FORMAT(r.data_registro, '%Y-%m') AS mes,
               SUM(r.energia_gerada) AS gerada,
               SUM(r.energia_consumida) AS consumida
        FROM registros r
        INNER JOIN fotovoltaicos f ON f.ID_fotovoltaicos = r.ID_fotovoltaicos
        WHERE r.data_registro BETWEEN ? AND ?
    ";
    $params = [$inicio, $fim];

    if ($idUsuario !== null) {
        $sql .= ' AND f.ID_USUARIO = ?';
        $params[] = $idUsuario;
    }

    $sql .= " GROUP BY DATE_FORMAT(r.data_registro, '%Y-%m')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $resultado = [];
    foreach ($stmt->fetchAll() as $linha) {
        $resultado[$linha['mes']] = [
            'gerada' => (int) $linha['gerada'],
            'consumida' => (int) $linha['consumida'],
        ];
    }

    return $resultado;
}

/** Último dia com lançamento (somando todos os sistemas do usuário). */
function ultimoDiaComRegistro(PDO $pdo, int $idUsuario): array|false
{
    $stmt = $pdo->prepare('
        SELECT r.data_registro,
               SUM(r.energia_gerada) AS gerada,
               SUM(r.energia_consumida) AS consumida
        FROM registros r
        INNER JOIN fotovoltaicos f ON f.ID_fotovoltaicos = r.ID_fotovoltaicos
        WHERE f.ID_USUARIO = ?
        GROUP BY r.data_registro
        ORDER BY r.data_registro DESC
        LIMIT 1
    ');
    $stmt->execute([$idUsuario]);

    return $stmt->fetch();
}

/** Últimos lançamentos com o nome do sistema. */
function ultimosRegistros(PDO $pdo, int $idUsuario, int $limite = 10): array
{
    $limite = max(1, min(100, $limite));

    $stmt = $pdo->prepare("
        SELECT r.ID_REGISTRO, r.data_registro, r.energia_gerada, r.energia_consumida,
               f.ID_fotovoltaicos, f.modelo_painel
        FROM registros r
        INNER JOIN fotovoltaicos f ON f.ID_fotovoltaicos = r.ID_fotovoltaicos
        WHERE f.ID_USUARIO = ?
        ORDER BY r.data_registro DESC, r.ID_REGISTRO DESC
        LIMIT {$limite}
    ");
    $stmt->execute([$idUsuario]);

    return $stmt->fetchAll();
}

/* ------------------------------------------------------------------ */
/* Visão geral (index.php)                                            */
/* ------------------------------------------------------------------ */

/** Junta todos os números da página "Visão geral" de um usuário. */
function montarVisaoGeral(PDO $pdo, int $idUsuario): array
{
    $hoje = new DateTimeImmutable('today');
    $sistemas = listarSistemas($pdo, $idUsuario);

    // Geração de hoje + último dia com lançamento
    $diaHoje = totaisPeriodo($pdo, $idUsuario, $hoje->format('Y-m-d'), $hoje->format('Y-m-d'));
    $ultimoDia = ultimoDiaComRegistro($pdo, $idUsuario);

    // Mês de referência: o mês atual; se ainda não tiver lançamentos, o último mês que tiver
    $inicioMesAtual = $hoje->modify('first day of this month');
    $mesAtual = totaisPeriodo($pdo, $idUsuario, $inicioMesAtual->format('Y-m-d'), $hoje->format('Y-m-d'));
    $mesEhAtual = $mesAtual['lancamentos'] > 0 || !$ultimoDia;

    if ($mesEhAtual) {
        $inicioRef = $inicioMesAtual;
        $fimRef = $hoje;
        $totaisRef = $mesAtual;
    } else {
        $inicioRef = (new DateTimeImmutable($ultimoDia['data_registro']))->modify('first day of this month');
        $fimRef = $inicioRef->modify('last day of this month');
        $totaisRef = totaisPeriodo($pdo, $idUsuario, $inicioRef->format('Y-m-d'), $fimRef->format('Y-m-d'));
    }

    // Comparação com o mês anterior (no mês atual, compara os mesmos dias: 1 até hoje)
    $inicioAnterior = $inicioRef->modify('first day of previous month');
    $fimAnteriorMes = $inicioAnterior->modify('last day of this month');
    $fimAnterior = $fimAnteriorMes;

    if ($mesEhAtual) {
        $dia = min((int) $hoje->format('j'), (int) $fimAnteriorMes->format('j'));
        $fimAnterior = $inicioAnterior->setDate(
            (int) $inicioAnterior->format('Y'),
            (int) $inicioAnterior->format('n'),
            $dia
        );
    }

    $totaisAnterior = totaisPeriodo($pdo, $idUsuario, $inicioAnterior->format('Y-m-d'), $fimAnterior->format('Y-m-d'));
    $variacao = $totaisAnterior['gerada'] > 0
        ? ($totaisRef['gerada'] - $totaisAnterior['gerada']) / $totaisAnterior['gerada'] * 100
        : null;

    // Autossuficiência: quanto do consumo foi coberto pela geração
    $autossuficiencia = $totaisRef['consumida'] > 0
        ? $totaisRef['gerada'] / $totaisRef['consumida'] * 100
        : null;

    // Gráfico: 4 meses terminando no mês de referência
    $inicioGrafico = $inicioRef->modify('-3 months');
    $porMes = totaisPorMes($pdo, $idUsuario, $inicioGrafico->format('Y-m-d'), $fimRef->modify('last day of this month')->format('Y-m-d'));
    $grafico = [];

    for ($i = 0; $i < 4; $i++) {
        $mes = $inicioGrafico->modify("+{$i} months");
        $chave = $mes->format('Y-m');
        $grafico[] = [
            'rotulo' => NOMES_MESES[(int) $mes->format('n')],
            'gerada' => $porMes[$chave]['gerada'] ?? 0,
            'ativo' => $chave === $inicioRef->format('Y-m'),
        ];
    }

    $maiorBarra = max(1, ...array_column($grafico, 'gerada'));
    foreach ($grafico as &$barra) {
        $barra['altura'] = round($barra['gerada'] / $maiorBarra * 100);
    }
    unset($barra);

    // Status dos sistemas
    $contagemStatus = ['Ativo' => 0, 'Manutencao' => 0, 'Inativo' => 0];
    foreach ($sistemas as $s) {
        $status = normalizarStatus($s['status_sistema']);
        $contagemStatus[$status] = ($contagemStatus[$status] ?? 0) + 1;
    }

    // Manutenções (somando todos os sistemas)
    $ultimaManutencao = null;
    $proximaManutencao = null;
    $qualidade = null;

    foreach ($sistemas as $s) {
        if ($s['ultima_manutencao'] && (!$ultimaManutencao || $s['ultima_manutencao'] > $ultimaManutencao)) {
            $ultimaManutencao = $s['ultima_manutencao'];
            $qualidade = $s['qualidade_sistema'];
        }

        $proxima = $s['proxima_agendada'] ?: $s['previsao_manutencao'];
        if ($proxima && (!$proximaManutencao || $proxima < $proximaManutencao)) {
            $proximaManutencao = $proxima;
        }
    }

    return [
        'sistemas' => $sistemas,
        'hoje' => $diaHoje,
        'ultimo_dia' => $ultimoDia ?: null,
        'mes_ref' => [
            'rotulo' => NOMES_MESES[(int) $inicioRef->format('n')] . '/' . $inicioRef->format('Y'),
            'eh_atual' => $mesEhAtual,
            'inicio' => $inicioRef->format('Y-m-d'),
            'fim' => $fimRef->format('Y-m-d'),
        ] + $totaisRef,
        'mes_anterior' => [
            'rotulo' => NOMES_MESES[(int) $inicioAnterior->format('n')] . '/' . $inicioAnterior->format('Y'),
            'inicio' => $inicioAnterior->format('Y-m-d'),
            'fim' => $fimAnterior->format('Y-m-d'),
        ] + $totaisAnterior,
        'variacao' => $variacao,
        'economia' => $totaisRef['gerada'] * TARIFA_KWH,
        'autossuficiencia' => $autossuficiencia,
        'grafico' => $grafico,
        'status' => $contagemStatus,
        'ultima_manutencao' => $ultimaManutencao,
        'proxima_manutencao' => $proximaManutencao,
        'qualidade' => $qualidade,
        'potencia' => estimarPotencia($sistemas),
        'registros' => ultimosRegistros($pdo, $idUsuario, 8),
    ];
}

/* ------------------------------------------------------------------ */
/* Alertas automáticos (alerts.php e contador do menu)                */
/* ------------------------------------------------------------------ */

/**
 * Gera alertas a partir dos dados reais dos sistemas.
 * Cada alerta: titulo, descricao, prioridade (alta|media|baixa), situacao (pendente|resolvido), cliente, data.
 */
function gerarAlertas(PDO $pdo, ?int $idUsuario): array
{
    $hoje = date('Y-m-d');
    $limiteSemLancamento = date('Y-m-d', strtotime('-30 days'));
    $limiteAgenda = date('Y-m-d', strtotime('+15 days'));
    $alertas = [];

    foreach (listarSistemas($pdo, $idUsuario) as $s) {
        $nomeSistema = '#' . $s['ID_fotovoltaicos'] . ' ' . $s['modelo_painel'];
        $base = ['cliente' => $s['cliente'] ?? '—', 'sistema' => $nomeSistema];
        $status = normalizarStatus($s['status_sistema']);

        if ($status === 'Inativo') {
            $alertas[] = $base + [
                'titulo' => 'Sistema inativo',
                'descricao' => "O sistema {$nomeSistema} está com status \"Inativo\" e não está gerando energia.",
                'prioridade' => 'alta', 'situacao' => 'pendente', 'data' => null,
            ];
        } elseif ($status === 'Manutencao') {
            $alertas[] = $base + [
                'titulo' => 'Sistema em manutenção',
                'descricao' => "O sistema {$nomeSistema} está em manutenção.",
                'prioridade' => 'media', 'situacao' => 'pendente', 'data' => null,
            ];
        }

        // Manutenção prevista que já passou e nenhuma visita futura agendada
        if (!$s['proxima_agendada'] && $s['previsao_manutencao'] && $s['previsao_manutencao'] < $hoje) {
            $alertas[] = $base + [
                'titulo' => 'Manutenção atrasada',
                'descricao' => 'A próxima manutenção estava prevista para ' . formatarData($s['previsao_manutencao']) . ' e nenhuma visita foi agendada.',
                'prioridade' => 'alta', 'situacao' => 'pendente', 'data' => $s['previsao_manutencao'],
            ];
        }

        // Visita marcada para os próximos 15 dias
        if ($s['proxima_agendada'] && $s['proxima_agendada'] <= $limiteAgenda) {
            $alertas[] = $base + [
                'titulo' => 'Manutenção agendada',
                'descricao' => 'Há uma visita marcada para ' . formatarData($s['proxima_agendada']) . '.',
                'prioridade' => 'baixa', 'situacao' => 'pendente', 'data' => $s['proxima_agendada'],
            ];
        }

        // Sistema ativo sem lançamentos recentes
        if ($status === 'Ativo') {
            if (!$s['ultimo_registro']) {
                $alertas[] = $base + [
                    'titulo' => 'Nenhum lançamento de energia',
                    'descricao' => 'Este sistema ainda não tem registros de geração e consumo. Lance os dados em Monitoramento.',
                    'prioridade' => 'media', 'situacao' => 'pendente', 'data' => null,
                ];
            } elseif ($s['ultimo_registro'] < $limiteSemLancamento) {
                $alertas[] = $base + [
                    'titulo' => 'Sem lançamentos recentes',
                    'descricao' => 'O último registro de energia foi em ' . formatarData($s['ultimo_registro']) . ' (há mais de 30 dias).',
                    'prioridade' => 'media', 'situacao' => 'pendente', 'data' => $s['ultimo_registro'],
                ];
            }
        }

        // Qualidade ruim na última manutenção
        if (in_array($s['qualidade_sistema'], ['Ruim', 'Regular'], true)) {
            $alertas[] = $base + [
                'titulo' => 'Qualidade do sistema: ' . $s['qualidade_sistema'],
                'descricao' => 'A última manutenção (' . formatarData($s['ultima_manutencao']) . ') avaliou o sistema como "' . $s['qualidade_sistema'] . '".',
                'prioridade' => $s['qualidade_sistema'] === 'Ruim' ? 'alta' : 'media',
                'situacao' => 'pendente', 'data' => $s['ultima_manutencao'],
            ];
        }
    }

    // Consumo maior que a geração no último mês com lançamentos
    foreach (deficitsDoUltimoMes($pdo, $idUsuario) as $d) {
        $alertas[] = [
            'cliente' => $d['cliente'] ?? '—',
            'sistema' => '#' . $d['ID_fotovoltaicos'] . ' ' . $d['modelo_painel'],
            'titulo' => 'Consumo maior que a geração',
            'descricao' => 'Em ' . rotuloMes($d['mes']) . ' o consumo foi de ' . formatarKwh((int) $d['consumida'])
                . ' e a geração de ' . formatarKwh((int) $d['gerada']) . '.',
            'prioridade' => 'baixa', 'situacao' => 'pendente', 'data' => null,
        ];
    }

    // Manutenções já realizadas entram como alertas resolvidos
    foreach (manutencoesRealizadas($pdo, $idUsuario) as $m) {
        $alertas[] = [
            'cliente' => $m['cliente'] ?? '—',
            'sistema' => '#' . $m['ID_fotovoltaicos'] . ' ' . $m['modelo_painel'],
            'titulo' => 'Manutenção realizada',
            'descricao' => 'Manutenção feita em ' . formatarData($m['data_agendada'])
                . ($m['tempo_estimado'] ? " ({$m['tempo_estimado']})" : '')
                . ($m['qualidade_sistema'] ? ' · qualidade avaliada: ' . $m['qualidade_sistema'] : '') . '.',
            'prioridade' => 'baixa', 'situacao' => 'resolvido', 'data' => $m['data_agendada'],
        ];
    }

    // Alta primeiro, depois média e baixa
    $ordem = ['alta' => 0, 'media' => 1, 'baixa' => 2];
    usort($alertas, static fn ($a, $b) => [$a['situacao'] === 'resolvido', $ordem[$a['prioridade']]]
        <=> [$b['situacao'] === 'resolvido', $ordem[$b['prioridade']]]);

    return $alertas;
}

function contarAlertasPendentes(PDO $pdo, ?int $idUsuario): int
{
    return count(array_filter(
        gerarAlertas($pdo, $idUsuario),
        static fn ($a) => $a['situacao'] === 'pendente'
    ));
}

/** Sistemas cujo consumo passou a geração no mês mais recente de cada um. */
function deficitsDoUltimoMes(PDO $pdo, ?int $idUsuario): array
{
    $sql = "
        SELECT f.ID_fotovoltaicos, f.modelo_painel, u.nome AS cliente,
               DATE_FORMAT(r.data_registro, '%Y-%m') AS mes,
               SUM(r.energia_gerada) AS gerada,
               SUM(r.energia_consumida) AS consumida
        FROM registros r
        INNER JOIN fotovoltaicos f ON f.ID_fotovoltaicos = r.ID_fotovoltaicos
        LEFT JOIN usuarios u ON u.ID_USUARIO = f.ID_USUARIO
        WHERE DATE_FORMAT(r.data_registro, '%Y-%m') = (
            SELECT DATE_FORMAT(MAX(r2.data_registro), '%Y-%m')
            FROM registros r2
            WHERE r2.ID_fotovoltaicos = f.ID_fotovoltaicos
        )
    ";
    $params = [];

    if ($idUsuario !== null) {
        $sql .= ' AND f.ID_USUARIO = ?';
        $params[] = $idUsuario;
    }

    $sql .= " GROUP BY f.ID_fotovoltaicos, f.modelo_painel, u.nome, DATE_FORMAT(r.data_registro, '%Y-%m')
              HAVING SUM(r.energia_consumida) > SUM(r.energia_gerada)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function manutencoesRealizadas(PDO $pdo, ?int $idUsuario, int $limite = 10): array
{
    $limite = max(1, min(100, $limite));
    $sql = '
        SELECT m.*, f.ID_fotovoltaicos, f.modelo_painel, u.nome AS cliente
        FROM manutencao_e_instalacao m
        INNER JOIN fotovoltaicos f ON f.ID_fotovoltaicos = m.ID_SISTEMAS
        LEFT JOIN usuarios u ON u.ID_USUARIO = f.ID_USUARIO
        WHERE m.data_agendada <= ?
    ';
    $params = [date('Y-m-d')];

    if ($idUsuario !== null) {
        $sql .= ' AND f.ID_USUARIO = ?';
        $params[] = $idUsuario;
    }

    $sql .= " ORDER BY m.data_agendada DESC LIMIT {$limite}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/* ------------------------------------------------------------------ */
/* Formatação                                                         */
/* ------------------------------------------------------------------ */

function formatarData(?string $data): string
{
    return $data ? date('d/m/Y', strtotime($data)) : '—';
}

function formatarKwh(float|int $valor, int $casas = 0): string
{
    return number_format($valor, $casas, ',', '.') . ' kWh';
}

function formatarReais(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/** "2026-10" => "Out/2026" */
function rotuloMes(string $anoMes): string
{
    [$ano, $mes] = array_pad(explode('-', $anoMes), 2, '');

    return (NOMES_MESES[(int) $mes] ?? $mes) . '/' . $ano;
}
