<?php
require_once __DIR__ . '/init.php';

$usuarioLogado = exigirLogin($pdo);

$mensagem_erro = '';
$mensagem_ok = $_SESSION['flash_config'] ?? '';
unset($_SESSION['flash_config']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $novo_email = trim((string) ($_POST['novo_email'] ?? ''));
  $novo_nome = trim((string) ($_POST['novo_nome'] ?? ''));

  if ($novo_nome === '' || $novo_email === '') {
    $mensagem_erro = 'Preencha nome e e-mail.';
  } elseif (!filter_var($novo_email, FILTER_VALIDATE_EMAIL)) {
    $mensagem_erro = 'Informe um e-mail válido.';
  } else {
    try {
      // Atualiza pelo ID do usuário logado.
      update(
        $pdo,
        'usuarios',
        [
          'nome' => substr($novo_nome, 0, 100),
          'email' => substr($novo_email, 0, 100),
        ],
        'ID_USUARIO = ?',
        [(int) $usuarioLogado['ID_USUARIO']]
      );

      // Atualiza a sessão para o menu mostrar o nome novo.
      $_SESSION['nome'] = $novo_nome;
      $_SESSION['email'] = $novo_email;
      $_SESSION['flash_config'] = 'Perfil atualizado com sucesso.';

      header('Location: config.php');
      exit;
    } catch (PDOException $e) {
      // 23000 = violação de UNIQUE.
      $mensagem_erro = $e->getCode() === '23000'
        ? 'Este e-mail já está em uso por outra conta.'
        : 'Não foi possível salvar agora. Tente novamente.';

      error_log('Erro ao salvar perfil: ' . $e->getMessage());
    }
  }
}

$valorNome = $_POST['novo_nome'] ?? $usuarioLogado['nome'];
$valorEmail = $_POST['novo_email'] ?? $usuarioLogado['email'];
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Configurações — Solar Control</title>

  <link rel="stylesheet" href="CSS/config.css">
  <link rel="stylesheet" href="CSS/menu.css?v=<?= filemtime(__DIR__ . '/CSS/menu.css') ?>">
</head>

<body>
  <?php include __DIR__ . '/menu.php'; ?>

  <main class="conteudo">
    <header class="topo">
      <div>
        <span class="crumb">Ajustar</span>
        <h1>Configurações</h1>
      </div>
    </header>

    <form method="POST" class="cfg" aria-label="Configurações da plataforma">
      <nav class="cfg-nav card" aria-label="Seções das configurações">
        <a href="#perfil" class="aba-perfil">Perfil</a>
        <a href="#notificacoes" class="aba-notif">Notificações</a>
      </nav>

      <div class="cfg-conteudo">
        <section id="perfil" class="cfg-sec card" aria-labelledby="titulo-perfil">
          <h2 id="titulo-perfil">Perfil</h2>

          <p class="desc">
            Como você aparece no Solar Control · Conta:
            <strong>
              <?= h(nomeTipoUsuario($usuarioLogado['TIPO'])) ?>
            </strong>

            <?php if (!empty($usuarioLogado['cidade'])): ?>
              · <?= h(
                $usuarioLogado['cidade']
                . ($usuarioLogado['estado']
                  ? ' - ' . $usuarioLogado['estado']
                  : '')
              ) ?>
            <?php endif; ?>
          </p>

          <div class="duas">
            <div>
              <label for="novo_nome">Nome</label>

              <input id="novo_nome" name="novo_nome" value="<?= h($valorNome) ?>" autocomplete="name">
            </div>

            <div>
              <label for="novo_email">E-mail</label>

              <input id="novo_email" name="novo_email" type="email" value="<?= h($valorEmail) ?>" autocomplete="email">
            </div>
          </div>
        </section>

        <section id="notificacoes" class="cfg-sec card" aria-labelledby="titulo-notificacoes">
          <h2 id="titulo-notificacoes">Notificações</h2>
          <p class="desc">Escolha como quer ser avisado.</p>

          <label class="opcao">
            <span>
              Alertas críticos por e-mail
              <small>Painel offline, falha de inversor</small>
            </span>

            <input type="checkbox" class="switch" name="alerta_email" checked>
          </label>

          <label class="opcao">
            <span>
              Resumo semanal
              <small>Toda segunda, às 08:00</small>
            </span>

            <input type="checkbox" class="switch" name="resumo">
          </label>

          <label class="opcao">
            <span>
              Lembrete de limpeza
              <small>Quando a Geração cair mais de 15%</small>
            </span>

            <input type="checkbox" class="switch" name="limpeza">
          </label>
        </section>

        <div class="acoes">
          <button type="reset" class="btn-ghost">
            Cancelar
          </button>

          <button type="submit" class="btn-verde" aria-describedby="aviso-salvar">
            Salvar alterações
          </button>
        </div>

        <p class="aviso" id="aviso-salvar">
          <?= h($mensagem_erro ?: $mensagem_ok) ?>
        </p>
      </div>
    </form>
  </main>
</body>

</html>