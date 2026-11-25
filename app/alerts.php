<?php
require_once __DIR__ . '/init.php';

$usuarioLogado = exigirLogin($pdo);
$equipe = ehEquipe($usuarioLogado);

// Alertas gerados a partir dos sistemas, registros e manutenções do banco.
// Cliente: só os dele · funcionário/adm: de todos os clientes.
$alertas = gerarAlertas($pdo, escopoUsuario($usuarioLogado));

$pendentes = array_values(array_filter($alertas, static fn ($a) => $a['situacao'] === 'pendente'));
$resolvidos = array_values(array_filter($alertas, static fn ($a) => $a['situacao'] === 'resolvido'));

$filtro = $_GET['filtro'] ?? 'pendentes';
$lista = match ($filtro) {
    'resolvidos' => $resolvidos,
    'todos' => $alertas,
    default => $pendentes,
};
if (!in_array($filtro, ['pendentes', 'resolvidos', 'todos'], true)) {
    $filtro = 'pendentes';
}

$nomesPrioridade = ['alta' => 'Alta', 'media' => 'Média', 'baixa' => 'Baixa'];
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Alertas | Solar Control</title>
  <link rel="stylesheet" href="CSS/alerts.css?v=<?= filemtime(__DIR__ . '/CSS/alerts.css') ?>">
</head>

<body>

  <?php include __DIR__ . '/menu.php'; ?>

  <div class="conteudo">

    <header class="textprincipal">
      <h1>Alertas</h1>
      <p>
        <?= $equipe
          ? 'Alertas automáticos de todos os clientes, gerados a partir dos dados do sistema.'
          : 'Alertas automáticos dos seus sistemas, gerados a partir dos seus dados.' ?>
      </p>
    </header>

    <!-- Filtros (GET): ?filtro=pendentes | resolvidos | todos -->
    <form class="botoes" method="get">
      <button type="submit" name="filtro" value="pendentes" aria-pressed="<?= $filtro === 'pendentes' ? 'true' : 'false' ?>">
        Pendentes <span><?= count($pendentes) ?></span>
      </button>

      <button type="submit" name="filtro" value="resolvidos" aria-pressed="<?= $filtro === 'resolvidos' ? 'true' : 'false' ?>">
        Resolvidos <span><?= count($resolvidos) ?></span>
      </button>

      <button type="submit" name="filtro" value="todos" aria-pressed="<?= $filtro === 'todos' ? 'true' : 'false' ?>">
        Todos <span><?= count($alertas) ?></span>
      </button>
    </form>

    <main>
      <section>
        <?php if (!$lista): ?>
          <article class="cartoes alerta-resolvido">
            <h3>Nenhum alerta aqui</h3>
            <p>
              <?= $filtro === 'resolvidos'
                ? 'Ainda não há manutenções concluídas.'
                : 'Tudo certo com os sistemas desta conta.' ?>
            </p>
          </article>
        <?php endif; ?>

        <?php foreach ($lista as $a): ?>
          <?php $classe = $a['situacao'] === 'resolvido' ? 'alerta-resolvido' : 'alerta-' . $a['prioridade']; ?>
          <article class="cartoes <?= $classe ?>">
            <h3><?= h($a['titulo']) ?></h3>

            <span class="etiqueta"><?= $a['situacao'] === 'resolvido' ? 'Resolvido' : 'Aberto' ?></span>
            <span class="etiqueta"><?= h($nomesPrioridade[$a['prioridade']]) ?></span>

            <p><?= h($a['descricao']) ?></p>

            <small>
              <?= $equipe ? h($a['cliente']) . ' · ' : '' ?><?= h($a['sistema']) ?>
              <?= $a['data'] ? ' · ' . formatarData($a['data']) : '' ?>
            </small>

            <details class="formularioabaixo">
              <summary class="verdetalhes">Ver detalhes</summary>
              <p>Cliente: <?= h($a['cliente']) ?></p>
              <p>Sistema: <?= h($a['sistema']) ?></p>
              <p>Prioridade: <?= h($nomesPrioridade[$a['prioridade']]) ?></p>
              <?php if ($a['situacao'] === 'pendente'): ?>
                <p>
                  <a class="botao" href="<?= str_contains($a['titulo'], 'lançamento') ? 'monitoramento.php' : 'paineis.php' ?>">
                    Resolver
                  </a>
                </p>
              <?php endif; ?>
            </details>
          </article>
        <?php endforeach; ?>
      </section>
    </main>

  </div>

</body>

</html>
