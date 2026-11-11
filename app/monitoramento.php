<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Projeto Integrador</title>
  <link rel="stylesheet" href="CSS/index.css">
</head>

<body>
  <aside class="menu-lateral">
    <a class="marca" href="index.php">
      solar<span>control</span>
    </a>

    <nav class="menu-links" aria-label="Navegação do painel">
      <p class="titulo-menu">NAVEGAÇÂO</p>

      <a href="index.php" aria-current="page">Visão geral</a>
      <a href="paineis.php">Painéis</a>
      <a href="monitoramento.php">Monitoramento</a>
      <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>

      <p class="titulo-menu">GESTÃO</p>

      <a href="alerts.php">Alertas</a>
      <a href="reports.php">Relatórios</a>
    </nav>

    <div class="menu-rodape">
      <nav class="menu-links" aria-label="Navegação da conta">
        <a href="profile.php">Meu perfil</a>
        <a href="config.php">Configurações</a>
      </nav>

      <div class="usuario">
        <span class="avatar">C</span>

        <div class="dados-usuario">
          <strong>Carlos Oliveira</strong>
          <small>Conta pessoal</small>
        </div>

        <a class="sair" href="auth/login.php">Sair</a>
      </div>
    </div>
  </aside>

  <main class="conteudo">
    <h1>MONITORA BB</h1>
    <p>Acompanhe sua instalação solar.</p>
  </main>
</body>
</html>