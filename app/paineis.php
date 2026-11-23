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
  <title>Painéis — SolarControl</title>
  <link rel="stylesheet" href="CSS/paineis.css">
</head>
<body>
<body>
  <?php include __DIR__ . '/menu.php'; ?>


  <main class="conteudo">
    <h1>Painéis</h1>
    <p class="subtitulo">Acompanhe os clientes, locais e sistemas da sua instalação solar.</p>

    <section class="resumo">
      <div class="resumo-card"><span>Clientes</span><strong>3</strong></div>
      <div class="resumo-card"><span>Locais</span><strong>3</strong></div>
      <div class="resumo-card"><span>Painéis</span><strong>76</strong></div>
      <div class="resumo-card"><span>Potência</span><strong>39,20 <small>kWp</small></strong></div>
    </section>

    <section class="tabela-card">
      <h2>Sistemas fotovoltaicos</h2>
      <table class="tabela">
        <tr><th>Cliente</th><th>Local</th><th>Painéis</th><th>Potência</th><th>Status</th></tr>
        <tr><td>Maria Oliveira</td><td>Residência</td><td>12 × 550 W</td><td>6,60 kWp</td><td><span class="status ativo">Ativo</span></td></tr>
        <tr><td>Padaria Sol Nascente</td><td>Loja Centro</td><td>24 × 450 W</td><td>10,80 kWp</td><td><span class="status ativo">Ativo</span></td></tr>
        <tr><td>Escola Técnica Verde</td><td>Bloco A</td><td>40 × 545 W</td><td>21,80 kWp</td><td><span class="status manutencao">Em manutenção</span></td></tr>
      </table>
    </section>

    <section class="tabela-card">
      <h2>Clientes</h2>
      <table class="tabela">
        <tr><th>Nome</th><th>E-mail</th><th>Telefone</th></tr>
        <tr><td>Maria Oliveira</td><td>maria@email.com</td><td>(11) 98888-1234</td></tr>
        <tr><td>Padaria Sol Nascente</td><td>contato@solnascente.com</td><td>(11) 4444-5678</td></tr>
        <tr><td>Escola Técnica Verde</td><td>adm@escolaverde.edu.br</td><td>(11) 3333-9090</td></tr>
      </table>
    </section>

    <section class="tabela-card">
      <h2>Locais</h2>
      <table class="tabela">
        <tr><th>Local</th><th>Cliente</th><th>Endereço</th><th>Cidade</th></tr>
        <tr><td>Residência</td><td>Maria Oliveira</td><td>Rua das Flores, 100</td><td>São Caetano do Sul / SP</td></tr>
        <tr><td>Loja Centro</td><td>Padaria Sol Nascente</td><td>Av. Industrial, 850</td><td>Santo André / SP</td></tr>
        <tr><td>Bloco A</td><td>Escola Técnica Verde</td><td>Rua Goiás, 1200</td><td>São Caetano do Sul / SP</td></tr>
      </table>
    </section>
  </main>
</body>
</html>
