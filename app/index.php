<?php
require_once __DIR__ . '/init.php';

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: login.php');
    exit;
}

?>

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
      <p class="titulo-menu">MENU</p>

      <a href="index.php" aria-current="page">Visão geral</a>
      <a href="paineis.php">Painéis</a>
      <a href="monitoramento.php">Monitoramento</a>
      <a href="alerts.php">Alertas</a>
      <a href="perguntas.php">FAQ</a>

      
    </nav>

    <div class="menu-rodape">
      <nav class="menu-links" aria-label="Navegação da conta">
        <p class="titulo-menu">GESTÃO</p>
        <a href="config.php">Configurações</a>
      </nav>

      <div class="usuario">
        <span class="avatar">C</span>

        <div class="dados-usuario">
          <strong><?php echo $_SESSION['nome']; ?></strong>
          <small>Conta pessoal</small>
        </div>

        <a class="sair" href="login.php">Sair</a>
      </div>
    </div>
  </aside>

  <main class="conteudo">
    <h1>Visão geral</h1>
    <p>Acompanhe os principais indicadores da sua instalação.</p>
 <!-- INDICADORES -->
    <section class="indicadores" aria-label="Indicadores principais">
      <article class="indicador card">
        <p class="letrasCard"><span>Geração hoje</span></p>
        <strong>19,5<small>kWh</small></strong>
        <p>Previsto até agora: 21,7 kWh · dia inteiro: 34,0 kWh</p>
      </article>

      <article class="indicador card">
         <p class="letrasCard"><span>Geração no mês</span></p>
        <strong>366<small>kWh</small></strong>
        <p><span class="negativo">-9,1%</span> em relação a 1–13 de outubro</p>
      </article>

      <article class="indicador card">
        <p class="letrasCard"><span>Economia no mês</span></p>
        <strong>R$ 336,71</strong>
        <p class="prescricao"><span>366 kWh × R$ 0,92 · consumo do mês: 283 kWh</span></p>
      </article>

      <article class="indicador card">
        <p class="letrasCard"><span>Desempenho</span></p>
        <strong>89,9<small>%</small></strong>
        <p><span class="negativo">−8,2 p.p.</span> desde a limpeza de 03/10</p>
        <div class="progresso" aria-hidden="true"><i style="width: 89.9%"></i></div>
      </article>
    </section>

  </main>
</body>
</html>
