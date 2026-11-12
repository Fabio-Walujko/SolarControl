<?php

require_once __DIR__ . '/banco/database.php';   
require_once __DIR__ . '/banco/crud.php';   

if ($_SERVER["REQUEST_METHOD"] == "POST") {

$email = $_POST['email'];
$senha = $_POST['senha'];
$nome = $_POST['nome'];

$pdo = database(); 

    if (!empty($email) && !empty($senha) && !empty($nome)) {
        try {
       
            $sql = "UPDATE usuarios (email, senha) VALUES (:email, :senha)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email, ':senha' => $senha]);
            header('Location: login.php');
            exit;

            
        } catch (PDOException $e) {
            $mensagem_erro = "Erro no banco de dados: " . $e->getMessage();
        }
    }
}

?>



<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Configurações — Solar Control</title>
  <link rel="stylesheet" href="CSS/config.css">
</head>

<body>
  <!-- MENU LATERAL -->
  <aside class="menu-lateral">
    <a class="marca" href="index.php">
      solar<span>control</span>
    </a>

    <nav class="menu-links" aria-label="Navegação do painel">
      <p class="titulo-menu">NAVEGAÇÂO</p>

      <a href="index.php" aria-current="page">Visão geral</a>
      <a href="paineis.php">Painéis</a>
      <a href="monitoramento.php">Monitoramento</a>
      <a href="perguntas.php">FAQ</a>
      <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>

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

  <!-- CONTEÚDO -->
  <main class="conteudo">
    <header class="topo">
      <div>
        <span class="crumb">Ajustar</span>
        <h1>Configurações</h1>
      </div>

      <div class="topo-acoes">
        <a class="link-alertas" href="alerts.php">Alertas</a>

        <a
          class="avatar-topo"
          href="profile.php"
          aria-label="Abrir meu perfil"
        >
          IR
        </a>
      </div>
    </header>

    <form class="cfg" aria-label="Configurações da plataforma">
      <!-- NAVEGAÇÃO DAS CONFIGURAÇÕES -->
      <nav class="cfg-nav card" aria-label="Seções das configurações">
        <a href="#perfil" class="aba-perfil">Perfil</a>
        <a href="#usina" class="aba-usina">Usina</a>
        <a href="#notificacoes" class="aba-notif">Notificações</a>
      </nav>

      <div class="cfg-conteudo">
        <!-- PERFIL -->
        <section
          id="perfil"
          class="cfg-sec card"
          aria-labelledby="titulo-perfil"
        >
          <h2 id="titulo-perfil">Perfil</h2>
          <p class="desc">Como você aparece no Solar Control.</p>

          <div class="duas">
            <div>
              <label for="nome">Nome</label>
              <input
                id="nome"
                name="nome"
                value="Igor"
                autocomplete="name"
              >
            </div>

            <div>
              <label for="email">E-mail</label>
              <input
                id="email"
                name="email"
                type="email"
                value="igor.rego@gm.com"
                autocomplete="email"
              >
            </div>
          </div>
        </section>

        <!-- USINA -->
        <section
          id="usina"
          class="cfg-sec card"
          aria-labelledby="titulo-usina"
        >
          <h2 id="titulo-usina">Usina</h2>
          <p class="desc">
            A tarifa é usada como referência para a economia em reais.
          </p>

          <div class="duas">
            <div>
              <label for="kwp">Capacidade instalada (kWp)</label>
              <input
                id="kwp"
                name="kwp"
                value="8,4"
                inputmode="decimal"
              >
            </div>

            <div>
              <label for="tarifa">Tarifa de energia (R$/kWh)</label>
              <input
                id="tarifa"
                name="tarifa"
                value="0,92"
                inputmode="decimal"
              >
            </div>
          </div>
        </section>

        <!-- NOTIFICAÇÕES -->
        <section
          id="notificacoes"
          class="cfg-sec card"
          aria-labelledby="titulo-notificacoes"
        >
          <h2 id="titulo-notificacoes">Notificações</h2>
          <p class="desc">Escolha como quer ser avisado.</p>

          <label class="opcao">
            <span>
              Alertas críticos por e-mail
              <small>Painel offline, falha de inversor</small>
            </span>

            <input
              type="checkbox"
              class="switch"
              name="alerta_email"
              checked
            >
          </label>

          <label class="opcao">
            <span>
              Notificações push
              <small>No navegador e no celular</small>
            </span>

            <input
              type="checkbox"
              class="switch"
              name="push"
              checked
            >
          </label>

          <label class="opcao">
            <span>
              Resumo semanal
              <small>Toda segunda, às 08:00</small>
            </span>

            <input
              type="checkbox"
              class="switch"
              name="resumo"
            >
          </label>

          <label class="opcao">
            <span>
              Lembrete de limpeza
              <small>Quando a eficiência cair mais de 10%</small>
            </span>

            <input
              type="checkbox"
              class="switch"
              name="limpeza"
            >
          </label>
        </section>

        <!-- BOTÕES -->
        <div class="acoes">
          <button type="reset" class="btn-ghost">
            Cancelar
          </button>

          <button
            type="button"
            class="btn-verde"
            aria-describedby="aviso-salvar"
          >
            Salvar alterações
          </button>
        </div>

        <p class="aviso" id="aviso-salvar">
          Tela demonstrativa: as alterações não são salvas.
        </p>
      </div>
    </form>
  </main>
</body>
</html>
