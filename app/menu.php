<?php
// Pega o nome do arquivo atual (ex: index.php, paineis.php, etc.)
$paginaAtual = basename($_SERVER['PHP_SELF']);

// $usuarioLogado é definido pela página com exigirLogin($pdo)
$usuarioMenu = $usuarioLogado ?? [
    'nome' => $_SESSION['nome'] ?? 'Usuário',
    'TIPO' => $_SESSION['TIPO'] ?? 'cliente',
];

// Quantidade de alertas pendentes do usuário (equipe vê de todos os clientes)
$alertasPendentesMenu = 0;
if (isset($usuarioLogado, $pdo)) {
    try {
        $alertasPendentesMenu = contarAlertasPendentes($pdo, escopoUsuario($usuarioLogado));
    } catch (Throwable $e) {
        error_log('Erro ao contar alertas: ' . $e->getMessage());
    }
}
?>

<aside class="menu-lateral">
  <a class="marca" href="index.php">
    solar<span>control</span>
  </a>

  <nav class="menu-links" aria-label="Navegação do painel">
    <p class="titulo-menu">MENU</p>

    <a href="index.php" <?php if ($paginaAtual == 'index.php') echo 'aria-current="page"'; ?>>Visão geral</a>
    <a href="paineis.php" <?php if ($paginaAtual == 'paineis.php') echo 'aria-current="page"'; ?>>Painéis</a>
    <a href="monitoramento.php" <?php if ($paginaAtual == 'monitoramento.php') echo 'aria-current="page"'; ?>>Monitoramento</a>
    <a href="alerts.php" <?php if ($paginaAtual == 'alerts.php') echo 'aria-current="page"'; ?>>
      Alertas
      <?php if ($alertasPendentesMenu > 0): ?>
        <span class="contador" aria-label="<?= (int) $alertasPendentesMenu ?> alertas pendentes"><?= (int) $alertasPendentesMenu ?></span>
      <?php endif; ?>
    </a>
    <a href="perguntas.php" <?php if ($paginaAtual == 'perguntas.php') echo 'aria-current="page"'; ?>>FAQ</a>
  </nav>

  <div class="menu-rodape">
    <nav class="menu-links" aria-label="Navegação da conta">
      <p class="titulo-menu">GESTÃO</p>
      <a href="config.php" <?php if ($paginaAtual == 'config.php') echo 'aria-current="page"'; ?>>Configurações</a>
    </nav>

    <div class="usuario">
      <span class="avatar"><?= h(inicialNome((string) $usuarioMenu['nome'])) ?></span>

      <div class="dados-usuario">
        <strong><?= h($usuarioMenu['nome']) ?></strong>
        <small><?= h(nomeTipoUsuario($usuarioMenu['TIPO'] ?? 'cliente')) ?></small>
      </div>

      <a class="sair" href="encerrarSessao.php">Sair</a>
    </div>
  </div>
</aside>
