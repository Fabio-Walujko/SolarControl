<?php

require_once __DIR__ . '/init.php';

$usuarioLogado = exigirLogin($pdo);
$equipe = ehEquipe($usuarioLogado);

$filtro = $_GET['filtro'] ?? 'pendentes';

if (!in_array($filtro, ['pendentes', 'resolvidos', 'todos', 'enviados'], true)) {
    $filtro = 'pendentes';
}

$erroForm = '';
$dadosForm = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = (string) ($_POST['acao'] ?? '');

    try {
        if (!validarCsrf($_POST['csrf'] ?? null)) {
            throw new InvalidArgumentException(
                'A página expirou. Recarregue e tente de novo.'
            );
        }

        if ($acao === 'criar') {
            criarAlerta($pdo, $usuarioLogado, $_POST);

            $_SESSION['aviso_alertas'] = $equipe
                ? 'Alerta criado.'
                : 'Alerta enviado! A equipe SolarControl vai analisar e responder por aqui.';

            $filtro = 'pendentes';
        } elseif (
            in_array(
                $acao,
                ['resolver_automatico', 'reabrir_automatico'],
                true
            )
        ) {
            atualizarAlertaAutomatico(
                $pdo,
                $usuarioLogado,
                (string) ($_POST['chave_automatico'] ?? ''),
                $acao
            );

            $_SESSION['aviso_alertas'] = $acao === 'resolver_automatico'
                ? 'Alerta marcado como resolvido para sua conta. O status real do sistema não foi alterado.'
                : 'Alerta reaberto.';
        } else {
            atualizarAlerta(
                $pdo,
                $usuarioLogado,
                (int) ($_POST['ID_ALERTA'] ?? 0),
                $acao,
                (string) ($_POST['resposta'] ?? '')
            );

            $_SESSION['aviso_alertas'] = match ($acao) {
                'resolver' => 'Alerta marcado como resolvido.',
                'reabrir' => 'Alerta reaberto.',
                default => 'Recado salvo.',
            };
        }

        header('Location: alerts.php?filtro=' . urlencode($filtro));
        exit;
    } catch (InvalidArgumentException $e) {
        $erroForm = $e->getMessage();
        $dadosForm = $acao === 'criar' ? $_POST : [];
    } catch (Throwable $e) {
        error_log('Erro ao salvar alerta: ' . $e->getMessage());

        $erroForm = 'Não foi possível salvar o alerta agora. Tente novamente.';
        $dadosForm = $acao === 'criar' ? $_POST : [];
    }
}

$aviso = $_SESSION['aviso_alertas'] ?? '';
unset($_SESSION['aviso_alertas']);

$resumoAlertas = resumoAlertasUsuario($pdo, $usuarioLogado);

$alertas = $resumoAlertas['todos'];
$manuais = $resumoAlertas['manuais'];
$pendentes = $resumoAlertas['pendentes'];
$resolvidos = $resumoAlertas['resolvidos'];

$enviados = array_values(array_filter(
    $manuais,
    static fn ($a) =>
        $a['id_autor'] === (int) $usuarioLogado['ID_USUARIO']
));

$lista = match ($filtro) {
    'resolvidos' => $resolvidos,
    'todos' => $alertas,
    'enviados' => $enviados,
    default => $pendentes,
};

$sistemasForm = listarSistemas($pdo, escopoUsuario($usuarioLogado));
$equipeForm = listarEquipe($pdo);
$csrf = tokenCsrf();

function dataHora(?string $valor): string
{
    return $valor
        ? date('d/m/Y H:i', strtotime($valor))
        : '—';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Alertas | Solar Control</title>

  <link
    rel="stylesheet"
    href="CSS/alerts.css?v=<?= filemtime(__DIR__ . '/CSS/alerts.css') ?>"
  >

  <link
    rel="stylesheet"
    href="CSS/menu.css?v=<?= filemtime(__DIR__ . '/CSS/menu.css') ?>"
  >
</head>

<body>

  <?php include __DIR__ . '/menu.php'; ?>

  <div class="conteudo">

    <header class="textprincipal">
      <h1>Alertas</h1>

      <p>
        <?php if ($equipe): ?>
          Alertas dos seus sistemas e mensagens enviadas para você ou para toda a equipe.
        <?php else: ?>
          Alertas dos seus sistemas. Viu algum problema? Crie um alerta e a equipe recebe na hora.
        <?php endif; ?>
      </p>
    </header>

    <?php if ($aviso !== ''): ?>
      <p class="aviso aviso-sucesso" role="status">
        <?= h($aviso) ?>
      </p>
    <?php endif; ?>

    <?php if ($erroForm !== ''): ?>
      <p class="aviso aviso-erro" role="alert">
        <?= h($erroForm) ?>
      </p>
    <?php endif; ?>

    <form class="botoes" method="get">
      <button
        type="submit"
        name="filtro"
        value="pendentes"
        aria-pressed="<?= $filtro === 'pendentes' ? 'true' : 'false' ?>"
      >
        Pendentes <span><?= count($pendentes) ?></span>
      </button>

      <button
        type="submit"
        name="filtro"
        value="resolvidos"
        aria-pressed="<?= $filtro === 'resolvidos' ? 'true' : 'false' ?>"
      >
        Resolvidos <span><?= count($resolvidos) ?></span>
      </button>

      <button
        type="submit"
        name="filtro"
        value="todos"
        aria-pressed="<?= $filtro === 'todos' ? 'true' : 'false' ?>"
      >
        Todos <span><?= count($alertas) ?></span>
      </button>

      <button
        type="submit"
        name="filtro"
        value="enviados"
        aria-pressed="<?= $filtro === 'enviados' ? 'true' : 'false' ?>"
      >
        Criados por mim <span><?= count($enviados) ?></span>
      </button>
    </form>

    <main>

      <details
        class="formularioabaixo criar-alerta"
        <?= $dadosForm ? 'open' : '' ?>
      >
        <summary class="verdetalhesemcima">
          + Criar alerta
        </summary>

        <form
          class="formularioemcima"
          method="post"
          action="alerts.php?filtro=<?= h($filtro) ?>"
        >
          <input type="hidden" name="acao" value="criar">
          <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

          <label for="titulo">Título do alerta:</label>
          <input
            type="text"
            id="titulo"
            name="titulo"
            maxlength="100"
            required
            placeholder="Ex.: Painel parou de gerar"
            value="<?= h($dadosForm['titulo'] ?? '') ?>"
          >

          <label for="descricao">Descrição:</label>
          <textarea
            id="descricao"
            name="descricao"
            maxlength="1000"
            required
            placeholder="Conte o que aconteceu, desde quando e o que você já tentou."
          ><?= h($dadosForm['descricao'] ?? '') ?></textarea>

          <label for="prioridade">Prioridade:</label>
          <select id="prioridade" name="prioridade">
            <?php foreach (PRIORIDADES_ALERTA as $valor => $texto): ?>
              <option
                value="<?= h($valor) ?>"
                <?= ($dadosForm['prioridade'] ?? 'media') === $valor ? 'selected' : '' ?>
              >
                <?= h($texto) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label for="ID_fotovoltaicos">Sistema:</label>
          <select id="ID_fotovoltaicos" name="ID_fotovoltaicos">
            <option value="">Nenhum sistema específico</option>

            <?php foreach ($sistemasForm as $s): ?>
              <option
                value="<?= (int) $s['ID_fotovoltaicos'] ?>"
                <?= (int) ($dadosForm['ID_fotovoltaicos'] ?? 0) === (int) $s['ID_fotovoltaicos'] ? 'selected' : '' ?>
              >
                #<?= (int) $s['ID_fotovoltaicos'] ?>
                <?= h($s['modelo_painel']) ?>
                <?= $equipe ? ' — ' . h($s['cliente'] ?? '—') : '' ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label for="ID_DESTINATARIO">Enviar para:</label>
          <select id="ID_DESTINATARIO" name="ID_DESTINATARIO">
            <option value="">
              Toda a equipe (administradores e funcionários)
            </option>

            <?php foreach ($equipeForm as $pessoa): ?>
              <?php
                if (
                    (int) $pessoa['ID_USUARIO']
                    === (int) $usuarioLogado['ID_USUARIO']
                ) {
                    continue;
                }
              ?>

              <option
                value="<?= (int) $pessoa['ID_USUARIO'] ?>"
                <?= (int) ($dadosForm['ID_DESTINATARIO'] ?? 0) === (int) $pessoa['ID_USUARIO'] ? 'selected' : '' ?>
              >
                <?= h($pessoa['nome']) ?>
                (<?= h(nomeTipoUsuario($pessoa['TIPO'])) ?>)
              </option>
            <?php endforeach; ?>
          </select>

          <small class="dica-form">
            Apenas o destinatário escolhido (ou toda a equipe), o autor
            e o dono do sistema podem consultar este alerta.
          </small>

          <button class="botao" type="submit">
            Enviar alerta
          </button>
        </form>
      </details>

      <section>

        <?php if (!$lista): ?>
          <article class="cartoes alerta-resolvido">
            <h3>Nenhum alerta aqui</h3>

            <p>
              <?php if ($filtro === 'enviados'): ?>
                Você ainda não criou nenhum alerta.
                Use o botão “+ Criar alerta” acima.
              <?php elseif ($filtro === 'resolvidos'): ?>
                Ainda não há alertas resolvidos.
              <?php else: ?>
                Nenhum alerta nesta lista. Consulte Painéis e Monitoramento
                para verificar a situação real dos sistemas.
              <?php endif; ?>
            </p>
          </article>
        <?php endif; ?>

        <?php foreach ($lista as $i => $a): ?>
          <?php
            $manual = !empty($a['manual']);

            $classe = $a['situacao'] === 'resolvido'
                ? 'alerta-resolvido'
                : 'alerta-' . $a['prioridade'];

            $podeAlterar = $manual && (
                $equipe
                || $a['id_autor'] === (int) $usuarioLogado['ID_USUARIO']
            );
          ?>

          <article class="cartoes <?= h($classe) ?>">
            <h3><?= h($a['titulo']) ?></h3>

            <span class="etiqueta">
              <?= $a['situacao'] === 'resolvido' ? 'Resolvido' : 'Aberto' ?>
            </span>

            <span class="etiqueta">
              <?= h(PRIORIDADES_ALERTA[$a['prioridade']] ?? $a['prioridade']) ?>
            </span>

            <span class="etiqueta <?= $manual ? 'etiqueta-manual' : '' ?>">
              <?= $manual ? 'Criado por usuário' : 'Automático' ?>
            </span>

            <p><?= nl2br(h($a['descricao'])) ?></p>

            <small>
              <?php if ($manual): ?>
                Enviado por <?= h($a['autor']) ?>
                para <?= h($a['destinatario']) ?>
                · <?= h(dataHora($a['criado_em'])) ?>
                · <?= h($a['sistema']) ?>
              <?php else: ?>
                <?= $equipe ? h($a['cliente']) . ' · ' : '' ?>
                <?= h($a['sistema']) ?>
                <?= $a['data'] ? ' · ' . h(formatarData($a['data'])) : '' ?>
              <?php endif; ?>
            </small>

            <?php if ($manual && $a['resposta']): ?>
              <p class="resposta">
                <strong>
                  <?= h($a['respondido_por'] ?? 'Equipe') ?>:
                </strong>

                <?= nl2br(h($a['resposta'])) ?>
              </p>
            <?php endif; ?>

            <details class="formularioabaixo">
              <summary class="verdetalhes">
                Ver detalhes
              </summary>

              <p>Cliente: <?= h($a['cliente']) ?></p>
              <p>Sistema: <?= h($a['sistema']) ?></p>

              <p>
                Prioridade:
                <?= h(PRIORIDADES_ALERTA[$a['prioridade']] ?? $a['prioridade']) ?>
              </p>

              <?php if ($manual && $a['atualizado_em']): ?>
                <p>
                  Última atualização:
                  <?= h(dataHora($a['atualizado_em'])) ?>
                </p>
              <?php endif; ?>

              <?php if ($podeAlterar): ?>

                <form
                  method="post"
                  action="alerts.php?filtro=<?= h($filtro) ?>"
                  class="form-recado"
                >
                  <input
                    type="hidden"
                    name="csrf"
                    value="<?= h($csrf) ?>"
                  >

                  <input
                    type="hidden"
                    name="ID_ALERTA"
                    value="<?= (int) $a['id'] ?>"
                  >

                  <label for="resposta<?= $i ?>">
                    <?= $equipe ? 'Responder ao cliente:' : 'Adicionar informação:' ?>
                  </label>

                  <textarea
                    id="resposta<?= $i ?>"
                    name="resposta"
                    maxlength="1000"
                    placeholder="Escreva um recado (opcional ao resolver)"
                  ></textarea>

                  <div class="acoes-alerta">
                    <button
                      class="botao botao-claro"
                      type="submit"
                      name="acao"
                      value="responder"
                    >
                      Salvar recado
                    </button>

                    <?php if ($a['situacao'] === 'pendente'): ?>
                      <button
                        class="botao"
                        type="submit"
                        name="acao"
                        value="resolver"
                      >
                        Marcar como resolvido
                      </button>
                    <?php else: ?>
                      <button
                        class="botao"
                        type="submit"
                        name="acao"
                        value="reabrir"
                      >
                        Reabrir alerta
                      </button>
                    <?php endif; ?>
                  </div>
                </form>

              <?php elseif (!$manual && !empty($a['gerenciavel'])): ?>

                <?php if (!empty($a['resolvido_em'])): ?>
                  <p>
                    Marcado como resolvido nesta conta em
                    <?= h(dataHora($a['resolvido_em'])) ?>.
                  </p>
                <?php endif; ?>

                <p>
                  Esta ação altera apenas a notificação nesta conta,
                  não o status real do sistema.
                </p>

                <form
                  method="post"
                  action="alerts.php?filtro=<?= h($filtro) ?>"
                  class="form-recado"
                >
                  <input
                    type="hidden"
                    name="csrf"
                    value="<?= h($csrf) ?>"
                  >

                  <input
                    type="hidden"
                    name="chave_automatico"
                    value="<?= h($a['chave_automatico']) ?>"
                  >

                  <div class="acoes-alerta">
                    <?php if ($a['situacao'] === 'pendente'): ?>
                      <button
                        class="botao"
                        type="submit"
                        name="acao"
                        value="resolver_automatico"
                      >
                        Marcar como resolvido
                      </button>
                    <?php else: ?>
                      <button
                        class="botao"
                        type="submit"
                        name="acao"
                        value="reabrir_automatico"
                      >
                        Reabrir alerta
                      </button>
                    <?php endif; ?>

                    <a
                      class="botao botao-claro"
                      href="<?= str_contains($a['titulo'], 'lançamento') ? 'monitoramento.php' : 'paineis.php' ?>"
                    >
                      Ver sistema
                    </a>
                  </div>
                </form>

              <?php endif; ?>
            </details>
          </article>
        <?php endforeach; ?>

      </section>
    </main>
  </div>

</body>
</html>
