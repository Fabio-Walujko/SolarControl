<?php
require_once __DIR__ . '/init.php';

// Usuário logado (lido do banco pelo ID salvo na sessão)
$usuarioLogado = exigirLogin($pdo);

// A identidade vem exclusivamente da sessão, nunca de ?cliente=.
$visao = montarVisaoGeral($pdo, escopoUsuario($usuarioLogado));

$mes = $visao['mes_ref'];
$anterior = $visao['mes_anterior'];
$status = $visao['status'];
$totalSistemas = count($visao['sistemas']);
$potencia = $visao['potencia'];

$nomesStatus = [
  'Ativo' => 'Ativo',
  'Manutencao' => 'Em manutenção',
  'Inativo' => 'Inativo',
];

$classesStatus = [
  'Ativo' => 'ok',
  'Manutencao' => 'meio',
  'Inativo' => 'ruim',
];

// Texto e cor do card "Status do Sistema"
if ($totalSistemas === 0) {
  $statusTitulo = 'Sem sistema';
  $statusClasse = '';
  $statusTexto = 'Nenhum sistema fotovoltaico cadastrado para esta conta.';
} elseif ($status['Inativo'] > 0) {
  $statusTitulo = $status['Inativo'] === $totalSistemas ? 'Inativo' : 'Atenção';
  $statusClasse = 'negativo';
  $statusTexto = "{$status['Ativo']} de {$totalSistemas} sistema(s) ativo(s) · {$status['Inativo']} inativo(s).";
} elseif ($status['Manutencao'] > 0) {
  $statusTitulo = 'Em manutenção';
  $statusClasse = 'atencao';
  $statusTexto = "{$status['Ativo']} de {$totalSistemas} sistema(s) ativo(s) · {$status['Manutencao']} em manutenção.";
} else {
  $statusTitulo = 'Online';
  $statusClasse = 'positivo';
  $statusTexto = "{$status['Ativo']} de {$totalSistemas} sistema(s) ativo(s) e operando normalmente.";
}

$autossuficiencia = $visao['autossuficiencia'];
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Visão geral — SolarControl</title>

  <link rel="stylesheet" href="CSS/index.css?v=<?= filemtime(__DIR__ . '/CSS/index.css') ?>">
  <link rel="stylesheet" href="CSS/menu.css?v=<?= filemtime(__DIR__ . '/CSS/menu.css') ?>">
</head>

<body>
  <?php include __DIR__ . '/menu.php'; ?>

  <main class="conteudo">
    <header class="cabecalho-visao">
      <div>
        <h1>Visão geral</h1>

        <p>
          Olá, <strong><?= h($usuarioLogado['nome']) ?></strong>!
          Acompanhe os indicadores da sua instalação.

          <?php if (!empty($usuarioLogado['cidade'])): ?>
            <span class="local">
              · <?= h(
                $usuarioLogado['cidade']
                . ($usuarioLogado['estado'] ? ' - ' . $usuarioLogado['estado'] : '')
              ) ?>
            </span>
          <?php endif; ?>
        </p>
      </div>
    </header>

    <section class="indicadores" aria-label="Indicadores principais">
      <!-- 1. Geração de hoje -->
      <article class="indicador card">
        <p class="letrasCard"><span>Geração hoje</span></p>

        <strong>
          <?= number_format($visao['hoje']['gerada'], 0, ',', '.') ?><small>kWh</small>
        </strong>

        <p>
          <?php if ($visao['hoje']['lancamentos'] > 0): ?>
            Consumo hoje: <?= formatarKwh($visao['hoje']['consumida']) ?>

          <?php elseif ($visao['ultimo_dia']): ?>
            Nenhum lançamento hoje · último:
            <?= formatarKwh((int) $visao['ultimo_dia']['gerada']) ?>
            em <?= formatarData($visao['ultimo_dia']['data_registro']) ?>

          <?php else: ?>
            Nenhum lançamento de energia ainda.
          <?php endif; ?>
        </p>
      </article>

      <!-- 2. Geração no mês de referência -->
      <article class="indicador card">
        <p class="letrasCard">
          <span>Geração em <?= h($mes['rotulo']) ?></span>
        </p>

        <strong>
          <?= number_format($mes['gerada'], 0, ',', '.') ?><small>kWh</small>
        </strong>

        <p>
          <?php if ($visao['variacao'] !== null): ?>
            <span class="<?= $visao['variacao'] >= 0 ? 'positivo' : 'negativo' ?>">
              <?= ($visao['variacao'] >= 0 ? '+' : '')
                . number_format($visao['variacao'], 1, ',', '.') ?>%
            </span>

            em relação a <?= h($anterior['rotulo']) ?>
            <?= $mes['eh_atual'] ? '(mesmo período)' : '' ?>
          <?php else: ?>
            Sem geração em <?= h($anterior['rotulo']) ?> para comparar.
          <?php endif; ?>

          <?php if (!$mes['eh_atual']): ?>
            <br>
            <small class="nota">
              Mês atual sem lançamentos; mostrando o último mês registrado.
            </small>
          <?php endif; ?>
        </p>
      </article>

      <!-- 3. Economia = geração × tarifa -->
      <article class="indicador card">
        <p class="letrasCard">
          <span>Economia em <?= h($mes['rotulo']) ?></span>
        </p>

        <strong><?= formatarReais($visao['economia']) ?></strong>

        <p class="prescricao">
          <span>
            <?= formatarKwh($mes['gerada']) ?> × <?= formatarReais(TARIFA_KWH) ?>
            · consumo do mês: <?= formatarKwh($mes['consumida']) ?>
          </span>
        </p>
      </article>

      <!-- 4. Autossuficiência = geração ÷ consumo -->
      <article class="indicador card">
        <p class="letrasCard"><span>Autossuficiência</span></p>

        <?php if ($autossuficiencia !== null): ?>
          <strong>
            <?= number_format($autossuficiencia, 1, ',', '.') ?><small>%</small>
          </strong>

          <p>
            A geração cobriu
            <span class="<?= $autossuficiencia >= 100 ? 'positivo' : 'negativo' ?>">
              <?= number_format($autossuficiencia, 1, ',', '.') ?>%
            </span>
            do consumo em <?= h($mes['rotulo']) ?>.
          </p>

          <div class="progresso" aria-hidden="true">
            <i style="width: <?= min(100, round($autossuficiencia, 1)) ?>%"></i>
          </div>
        <?php else: ?>
          <strong>—</strong>
          <p>Sem consumo registrado em <?= h($mes['rotulo']) ?>.</p>
        <?php endif; ?>
      </article>
    </section>

    <section class="resumo-simples" aria-label="Informações gerais do sistema">
      <article class="indicador card card-grande">
        <p class="letrasCard"><span>Status do Sistema</span></p>
        <strong class="<?= $statusClasse ?>"><?= h($statusTitulo) ?></strong>
        <p><?= h($statusTexto) ?></p>
      </article>

      <article class="indicador card card-grande">
        <p class="letrasCard"><span>Última Manutenção</span></p>

        <strong>
          <?= $visao['ultima_manutencao']
            ? formatarData($visao['ultima_manutencao'])
            : 'Sem registro' ?>
        </strong>

        <p>
          <?php if ($visao['qualidade']): ?>
            Qualidade avaliada: <?= h($visao['qualidade']) ?> ·
          <?php endif; ?>

          <?php if ($visao['proxima_manutencao']): ?>
            Próxima manutenção: <?= formatarData($visao['proxima_manutencao']) ?>.
          <?php else: ?>
            Nenhuma manutenção prevista.
          <?php endif; ?>
        </p>
      </article>

      <article class="indicador card card-grande">
        <p class="letrasCard"><span>Potência Instalada</span></p>

        <strong>
          <?= $potencia['kwp'] > 0
            ? number_format($potencia['kwp'], 2, ',', '.')
            : '—' ?><small>kWp</small>
        </strong>

        <p>
          <?= (int) $potencia['modulos'] ?> módulo(s)
          em <?= $totalSistemas ?> sistema(s)
          · Tarifa atual: <?= formatarReais(TARIFA_KWH) ?>/kWh.

          <?php if ($potencia['sem_potencia'] > 0): ?>
            <br>
            <small class="nota">
              <?= (int) $potencia['sem_potencia'] ?>
              modelo(s) sem potência (W) no nome.
            </small>
          <?php endif; ?>
        </p>
      </article>

      <article class="indicador card card-grande">
        <p class="letrasCard">
          <span>Geração nos últimos meses (kWh)</span>
        </p>

        <div class="mini-grafico" role="img" aria-label="Gráfico de geração dos últimos 4 meses">
          <?php foreach ($visao['grafico'] as $barra): ?>
            <div class="coluna">
              <small><?= number_format($barra['gerada'], 0, ',', '.') ?></small>

              <div class="barra-box">
                <div class="barra <?= $barra['ativo'] ? 'ativa' : '' ?>" style="height: <?= $barra['altura'] ?>%;"></div>
              </div>

              <span><?= h($barra['rotulo']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </article>
    </section>

    <!-- Tabela: sistemas do usuário -->
    <section class="card bloco-tabela" aria-labelledby="titulo-sistemas">
      <div class="bloco-titulo">
        <h2 id="titulo-sistemas">Meus sistemas</h2>
        <a href="paineis.php">Gerenciar</a>
      </div>

      <?php if (!$visao['sistemas']): ?>
        <p class="vazio">
          Nenhum sistema cadastrado.
          Cadastre o primeiro em <a href="monitoramento.php">Monitoramento</a>.
        </p>
      <?php else: ?>
        <div class="tabela-wrapper">
          <table class="tabela-usuario">
            <thead>
              <tr>
                <th>ID</th>
                <th>Modelo</th>
                <th>Instalação</th>
                <th>Registros</th>
                <th>Último registro</th>
                <th>Próx. manutenção</th>
                <th>Status</th>
              </tr>
            </thead>

            <tbody>
              <?php foreach ($visao['sistemas'] as $s): ?>
                <?php $st = normalizarStatus($s['status_sistema']); ?>

                <tr>
                  <td>#<?= (int) $s['ID_fotovoltaicos'] ?></td>
                  <td><?= h($s['modelo_painel']) ?></td>
                  <td><?= formatarData($s['data_intalacao']) ?></td>
                  <td><?= (int) $s['total_registros'] ?></td>
                  <td><?= formatarData($s['ultimo_registro']) ?></td>

                  <td>
                    <?= formatarData(
                      $s['proxima_agendada'] ?: $s['previsao_manutencao']
                    ) ?>
                  </td>

                  <td>
                    <span class="status <?= $classesStatus[$st] ?? '' ?>">
                      <?= h($nomesStatus[$st] ?? $s['status_sistema']) ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <!-- Tabela: últimos lançamentos de energia -->
    <section class="card bloco-tabela" aria-labelledby="titulo-registros">
      <div class="bloco-titulo">
        <h2 id="titulo-registros">Últimos lançamentos</h2>
        <a href="monitoramento.php">Ver histórico</a>
      </div>

      <?php if (!$visao['registros']): ?>
        <p class="vazio">Nenhum lançamento de geração e consumo ainda.</p>
      <?php else: ?>
        <div class="tabela-wrapper">
          <table class="tabela-usuario">
            <thead>
              <tr>
                <th>Data</th>
                <th>Sistema</th>
                <th>Gerada</th>
                <th>Consumida</th>
                <th>Saldo</th>
              </tr>
            </thead>

            <tbody>
              <?php foreach ($visao['registros'] as $r): ?>
                <?php
                $saldo = (int) $r['energia_gerada']
                  - (int) $r['energia_consumida'];
                ?>

                <tr>
                  <td><?= formatarData($r['data_registro']) ?></td>

                  <td>
                    #<?= (int) $r['ID_fotovoltaicos'] ?>
                    · <?= h($r['modelo_painel']) ?>
                  </td>

                  <td><?= formatarKwh((int) $r['energia_gerada']) ?></td>
                  <td><?= formatarKwh((int) $r['energia_consumida']) ?></td>

                  <td class="<?= $saldo >= 0 ? 'positivo' : 'negativo' ?>">
                    <?= ($saldo >= 0 ? '+' : '') . formatarKwh($saldo) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </main>
</body>

</html>