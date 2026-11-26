<?php

require_once __DIR__ . '/init.php';

// Todos os cargos acessam apenas os sistemas da própria conta.
$usuarioLogado = exigirLogin($pdo);
$idUsuario = escopoUsuario($usuarioLogado);

/* h() agora fica em sessao.php e é usada por todas as páginas */

/* Status: valor salvo no banco => texto exibido */
$opcoes = [
    'Ativo' => 'Ativo',
    'Manutencao' => 'Em manutenção',
    'Inativo' => 'Inativo',
];

$cores = [
    'Ativo' => 'ok',
    'Manutencao' => 'meio',
    'Em manutenção' => 'meio',
    'Inativo' => 'ruim',
];

/* Siglas para a coluna enderecos.estado */
$estados = [
    'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO',
    'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI',
    'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
];

$erro = '';

/* Excluir o sistema e seus registros relacionados */
if (isset($_POST['excluir'])) {
    $idExcluir = (int) $_POST['excluir'];

    if (!podeAcessarSistema($pdo, $idExcluir, $usuarioLogado)) {
        $erro = 'Você não tem permissão para excluir este sistema.';
    } else {
        try {
            $pdo->beginTransaction();

            delete(
                $pdo,
                'registros',
                'ID_fotovoltaicos = ?',
                [$idExcluir]
            );

            delete(
                $pdo,
                'manutencao_e_instalacao',
                'ID_SISTEMAS = ?',
                [$idExcluir]
            );

            delete(
                $pdo,
                'fotovoltaicos',
                'ID_fotovoltaicos = ? AND ID_USUARIO = ?',
                [$idExcluir, $idUsuario]
            );

            $pdo->commit();

            header('Location: paineis.php');
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Erro ao excluir sistema: ' . $e->getMessage());
            $erro = 'Não foi possível excluir este sistema.';
        }
    }
}

/* Salvar: atualiza se houver ID; caso contrário, cadastra */
if (isset($_POST['modelo_painel'])) {
    // Ignora ID_USUARIO/cliente_antes enviados por formulário adulterado.
    $idSalvar = (int) ($_POST['id'] ?? 0);

    $dados = [
        'ID_USUARIO' => $idUsuario,
        'modelo_painel' => trim($_POST['modelo_painel']),
        'data_intalacao' => $_POST['data_intalacao'],
        'status_sistema' => $_POST['status_sistema'],
    ];

    /* O CEP é salvo apenas com os 8 números */
    $local = [
        'CEP' => preg_replace('/\D/', '', $_POST['CEP']),
        'estado' => $_POST['estado'],
        'cidade' => trim($_POST['cidade']),
        'logradouro' => trim($_POST['logradouro']),
        'numero' => trim($_POST['numero']),
    ];

    $mexerNoLocal = implode('', $local) !== '';

    if (
        $idSalvar > 0
        && !podeAcessarSistema($pdo, $idSalvar, $usuarioLogado)
    ) {
        $erro = 'Você não tem permissão para alterar este sistema.';
    } elseif (!buscarUsuario($pdo, $dados['ID_USUARIO'])) {
        $erro = 'Escolha um cliente válido.';
    } elseif (!isset($opcoes[$dados['status_sistema']])) {
        $erro = 'Escolha um status da lista.';
    } elseif ($local['CEP'] !== '' && strlen($local['CEP']) !== 8) {
        $erro = 'O CEP precisa ter 8 números.';
    } else {
        if ($idSalvar > 0) {
            update(
                $pdo,
                'fotovoltaicos',
                $dados,
                'ID_fotovoltaicos = ? AND ID_USUARIO = ?',
                [$idSalvar, $idUsuario]
            );
        } else {
            create($pdo, 'fotovoltaicos', $dados);
        }

        /* Atualiza somente o endereço da própria conta. */
        if ($mexerNoLocal) {
            $cliente = read(
                $pdo,
                'usuarios',
                'ID_USUARIO = ?',
                [$dados['ID_USUARIO']]
            );

            if ($cliente && $cliente['id_endereco']) {
                update(
                    $pdo,
                    'enderecos',
                    $local,
                    'ID_ENDERECO = ?',
                    [$cliente['id_endereco']]
                );
            } else {
                $idEndereco = create($pdo, 'enderecos', $local);

                update(
                    $pdo,
                    'usuarios',
                    ['id_endereco' => $idEndereco],
                    'ID_USUARIO = ?',
                    [$dados['ID_USUARIO']]
                );
            }
        }

        header('Location: paineis.php');
        exit;
    }
}

/* Buscar sistema para edição */
$editar = [];

if (
    isset($_GET['editar'])
    && podeAcessarSistema($pdo, (int) $_GET['editar'], $usuarioLogado)
) {
    $busca = $pdo->prepare('
        SELECT f.*, e.CEP, e.estado, e.cidade, e.logradouro, e.numero
        FROM fotovoltaicos f
        LEFT JOIN usuarios u ON u.ID_USUARIO = f.ID_USUARIO
        LEFT JOIN enderecos e ON e.ID_ENDERECO = u.id_endereco
        WHERE f.ID_fotovoltaicos = ? AND f.ID_USUARIO = ?
    ');

    $busca->execute([(int) $_GET['editar'], $idUsuario]);
    $editar = $busca->fetch() ?: [];
}

/* Manter os dados digitados se houver erro ao salvar */
if ($erro && isset($_POST['modelo_painel'])) {
    $editar = array_merge(
        $editar,
        $_POST,
        ['ID_fotovoltaicos' => $idSalvar > 0 ? $idSalvar : '']
    );
}

/* Sistemas, registros e manutenções da conta autenticada. */
$sqlSistemas = '
    SELECT
        f.*,
        u.nome AS cliente,
        e.CEP,
        e.logradouro,
        e.numero,
        e.cidade,
        e.estado,
        (
            SELECT COUNT(*)
            FROM registros r
            WHERE r.ID_fotovoltaicos = f.ID_fotovoltaicos
        ) AS total_registros,
        COALESCE(
            (
                SELECT MIN(m.data_agendada)
                FROM manutencao_e_instalacao m
                WHERE m.ID_SISTEMAS = f.ID_fotovoltaicos
                  AND m.data_agendada >= CURDATE()
            ),
            (
                SELECT MAX(m.previsao_da_proxima_manutencao)
                FROM manutencao_e_instalacao m
                WHERE m.ID_SISTEMAS = f.ID_fotovoltaicos
            )
        ) AS proxima_manutencao
    FROM fotovoltaicos f
    LEFT JOIN usuarios u ON u.ID_USUARIO = f.ID_USUARIO
    LEFT JOIN enderecos e ON e.ID_ENDERECO = u.id_endereco
    WHERE f.ID_USUARIO = ?
    ORDER BY u.nome
';

$stmtSistemas = $pdo->prepare($sqlSistemas);
$stmtSistemas->execute([$idUsuario]);
$sistemas = $stmtSistemas->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painéis — SolarControl</title>

    <link rel="stylesheet" href="CSS/paineis.css?v=<?= filemtime(__DIR__ . '/CSS/paineis.css') ?>">
    <link rel="stylesheet" href="CSS/menu.css?v=<?= filemtime(__DIR__ . '/CSS/menu.css') ?>">
</head>

<body>
    <?php include __DIR__ . '/menu.php'; ?>

    <main class="conteudo">
        <h1>Painéis</h1>

        <p class="dica">
            Você está vendo apenas os sistemas da sua conta.
        </p>

        <?php if ($erro): ?>
            <p class="erro-msg"><?= h($erro) ?></p>
        <?php endif; ?>

        <form class="caixa form" method="post">
            <h2>
                <?= !empty($editar['ID_fotovoltaicos'])
                    ? 'Editar sistema'
                    : 'Cadastrar novo sistema' ?>
            </h2>

            <input
                type="hidden"
                name="id"
                value="<?= h($editar['ID_fotovoltaicos'] ?? '') ?>"
            >

            <div class="grade grade-sistema">
                <label>
                    Conta
                    <input
                        type="text"
                        value="<?= h($usuarioLogado['nome']) ?>"
                        readonly
                    >
                </label>

                <label>
                    Modelo do painel
                    <input
                        type="text"
                        name="modelo_painel"
                        maxlength="100"
                        value="<?= h($editar['modelo_painel'] ?? '') ?>"
                        required
                    >
                </label>

                <label>
                    Data de instalação
                    <input
                        type="date"
                        name="data_intalacao"
                        value="<?= h($editar['data_intalacao'] ?? '') ?>"
                        required
                    >
                </label>

                <label>
                    Status
                    <select name="status_sistema">
                        <?php foreach ($opcoes as $valor => $texto): ?>
                            <option
                                value="<?= $valor ?>"
                                <?= ($cores[$editar['status_sistema'] ?? ''] ?? '')
                                    === $cores[$valor] ? 'selected' : '' ?>
                            >
                                <?= $texto ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <h3>Local do cliente</h3>

            <p class="dica">
                O local é do cliente: se ele tiver mais de um sistema,
                a mudança vale para todos.
                Deixe tudo em branco para não alterar.
            </p>

            <div class="grade grade-local">
                <label>
                    CEP
                    <input
                        type="text"
                        name="CEP"
                        maxlength="9"
                        inputmode="numeric"
                        placeholder="00000-000"
                        value="<?= h($editar['CEP'] ?? '') ?>"
                    >
                </label>

                <label>
                    Rua
                    <input
                        type="text"
                        name="logradouro"
                        maxlength="50"
                        value="<?= h($editar['logradouro'] ?? '') ?>"
                    >
                </label>

                <label>
                    Número
                    <input
                        type="text"
                        name="numero"
                        maxlength="5"
                        value="<?= h($editar['numero'] ?? '') ?>"
                    >
                </label>

                <label>
                    Cidade
                    <input
                        type="text"
                        name="cidade"
                        maxlength="50"
                        value="<?= h($editar['cidade'] ?? '') ?>"
                    >
                </label>

                <label>
                    Estado
                    <select name="estado">
                        <option value="">—</option>

                        <?php foreach ($estados as $uf): ?>
                            <option
                                <?= strtoupper($editar['estado'] ?? '') === $uf
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= $uf ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <div class="botoes">
                <?php if (!empty($editar['ID_fotovoltaicos'])): ?>
                    <a class="botao-cancelar" href="paineis.php">
                        Cancelar
                    </a>
                <?php endif; ?>

                <button class="botao-salvar" type="submit">
                    <?= !empty($editar['ID_fotovoltaicos'])
                        ? 'Salvar alterações'
                        : 'Cadastrar' ?>
                </button>
            </div>
        </form>

        <table class="caixa">
            <tr>
                <th>Cliente</th>
                <th>Local</th>
                <th>Cidade</th>
                <th>Modelo</th>
                <th>Instalação</th>
                <th>Registros</th>
                <th>Próx. manutenção</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>

            <?php foreach ($sistemas as $s): ?>
                <tr>
                    <td><?= h($s['cliente'] ?? '—') ?></td>

                    <td>
                        <?= $s['logradouro']
                            ? h($s['logradouro'] . ', ' . $s['numero'])
                            : '—' ?>

                        <?php if ($s['CEP']): ?>
                            <small class="cep">
                                CEP <?= h(
                                    substr($s['CEP'], 0, 5)
                                    . '-'
                                    . substr($s['CEP'], 5)
                                ) ?>
                            </small>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?= $s['cidade']
                            ? h(
                                $s['cidade']
                                . ($s['estado'] ? ' - ' . $s['estado'] : '')
                            )
                            : '—' ?>
                    </td>

                    <td><?= h($s['modelo_painel'] ?? '') ?></td>

                    <td>
                        <?= $s['data_intalacao']
                            ? date('d/m/Y', strtotime($s['data_intalacao']))
                            : '—' ?>
                    </td>

                    <td><?= $s['total_registros'] ?></td>

                    <td>
                        <?= $s['proxima_manutencao']
                            ? date('d/m/Y', strtotime($s['proxima_manutencao']))
                            : '—' ?>
                    </td>

                    <td>
                        <span class="status <?= $cores[$s['status_sistema'] ?? ''] ?? '' ?>">
                            <?= h(
                                $opcoes[$s['status_sistema'] ?? '']
                                ?? $s['status_sistema']
                            ) ?>
                        </span>
                    </td>

                    <td class="col-acoes">
                        <a href="paineis.php?editar=<?= $s['ID_fotovoltaicos'] ?>">
                            Editar
                        </a>

                        <form
                            method="post"
                            onsubmit="return confirm('Excluir este sistema? Os registros de energia e as manutenções dele também serão apagados.')"
                        >
                            <button
                                type="submit"
                                name="excluir"
                                value="<?= $s['ID_fotovoltaicos'] ?>"
                            >
                                Excluir
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (!$sistemas): ?>
                <tr>
                    <td colspan="9">
                        Nenhum sistema cadastrado ainda.
                    </td>
                </tr>
            <?php endif; ?>
        </table>
    </main>
</body>
</html>
