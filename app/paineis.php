<?php

require_once __DIR__ . '/init.php';

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: login.php');
    exit;
}

function h($texto)
{
    return htmlspecialchars((string) $texto);
}

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

/* Tipos de usuário para separar a lista */
$tipos = [
    'cliente' => 'Clientes',
    'funcionario' => 'Funcionários',
    'adm' => 'Administradores',
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
    try {
        $pdo->beginTransaction();

        delete($pdo, 'registros', 'ID_fotovoltaicos = ?', [$_POST['excluir']]);
        delete($pdo, 'manutencao_e_instalacao', 'ID_SISTEMAS = ?', [$_POST['excluir']]);
        delete($pdo, 'fotovoltaicos', 'ID_fotovoltaicos = ?', [$_POST['excluir']]);

        $pdo->commit();

        header('Location: paineis.php');
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $erro = 'Não foi possível excluir este sistema.';
    }
}

/* Salvar: atualiza se houver ID; caso contrário, cadastra */
if (isset($_POST['modelo_painel'])) {
    $dados = [
        'ID_USUARIO' => $_POST['ID_USUARIO'],
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
    $trocouCliente = $_POST['cliente_antes'] !== ''
        && $_POST['cliente_antes'] != $_POST['ID_USUARIO'];

    if (!isset($opcoes[$dados['status_sistema']])) {
        $erro = 'Escolha um status da lista.';
    } elseif ($local['CEP'] !== '' && strlen($local['CEP']) !== 8) {
        $erro = 'O CEP precisa ter 8 números.';
    } else {
        if ($_POST['id'] !== '') {
            update(
                $pdo,
                'fotovoltaicos',
                $dados,
                'ID_fotovoltaicos = ?',
                [$_POST['id']]
            );
        } else {
            create($pdo, 'fotovoltaicos', $dados);
        }

        /* Se o cliente mudou, não copia o endereço antigo para o novo */
        if ($mexerNoLocal && !$trocouCliente) {
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

if (isset($_GET['editar'])) {
    $busca = $pdo->prepare('
        SELECT f.*, e.CEP, e.estado, e.cidade, e.logradouro, e.numero
        FROM fotovoltaicos f
        LEFT JOIN usuarios u ON u.ID_USUARIO = f.ID_USUARIO
        LEFT JOIN enderecos e ON e.ID_ENDERECO = u.id_endereco
        WHERE f.ID_fotovoltaicos = ?
    ');

    $busca->execute([$_GET['editar']]);
    $editar = $busca->fetch() ?: [];
}

/* Manter os dados digitados se houver erro ao salvar */
if ($erro && isset($_POST['modelo_painel'])) {
    $editar = array_merge(
        $editar,
        $_POST,
        ['ID_fotovoltaicos' => $_POST['id']]
    );
}

/* Buscar sistemas, clientes, registros e próxima manutenção */
$sistemas = $pdo->query('
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
    ORDER BY u.nome
')->fetchAll();

$clientes = $pdo->query(
    'SELECT ID_USUARIO, nome, TIPO FROM usuarios ORDER BY nome'
)->fetchAll();

$nome = $_SESSION['nome'] ?? 'Usuário';
$inicial = preg_match('/./u', $nome, $letra) ? $letra[0] : '?';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painéis — SolarControl</title>
    <link rel="stylesheet" href="CSS/paineis.css?v=<?= filemtime(__DIR__ . '/CSS/paineis.css') ?>">
</head>
<body>

    <?php include __DIR__ . '/menu.php'; ?>

    <main class="conteudo">
        <h1>Painéis</h1>

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

            <input
                type="hidden"
                name="cliente_antes"
                value="<?= h($editar['cliente_antes'] ?? $editar['ID_USUARIO'] ?? '') ?>"
            >

            <div class="grade grade-sistema">
                <label>
                    Cliente
                    <select name="ID_USUARIO" required>
                        <option value="">Escolha o cliente</option>

                        <?php foreach ($tipos as $tipo => $grupo): ?>
                            <optgroup label="<?= $grupo ?>">
                                <?php foreach ($clientes as $c): ?>
                                    <?php if (($c['TIPO'] ?: 'cliente') === $tipo): ?>
                                        <option
                                            value="<?= $c['ID_USUARIO'] ?>"
                                            <?= ($editar['ID_USUARIO'] ?? '') == $c['ID_USUARIO']
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            <?= h($c['nome']) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
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
                                <?= ($cores[$editar['status_sistema'] ?? ''] ?? '') === $cores[$valor]
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= $texto ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <h3>Local do cliente</h3>

            <p class="dica">
                O local é do cliente: se ele tiver mais de um sistema, a mudança vale para todos.
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
                    <a class="botao-cancelar" href="paineis.php">Cancelar</a>
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
                                CEP <?= h(substr($s['CEP'], 0, 5) . '-' . substr($s['CEP'], 5)) ?>
                            </small>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?= $s['cidade']
                            ? h($s['cidade'] . ($s['estado'] ? ' - ' . $s['estado'] : ''))
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
                            <?= h($opcoes[$s['status_sistema'] ?? ''] ?? $s['status_sistema']) ?>
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
                    <td colspan="9">Nenhum sistema cadastrado ainda.</td>
                </tr>
            <?php endif; ?>
        </table>
    </main>
</body>
</html>
