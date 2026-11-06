<?php

require_once __DIR__ . '/banco/database.php';   
require_once __DIR__ . '/banco/crud.php';   

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

$pdo = database(); 

    if (!empty($email) && !empty($senha)) {
        try {
            $sql = "SELECT * FROM usuarios WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['email' => $email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario && $usuario['senha'] == $senha) {
                header('Location: index.php');
                exit;
            } else {
                $mensagem_erro = "E-mail ou senha inválidos.";
            }
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SolarControl</title>
  <link rel="stylesheet" href="CSS/login.css">
</head>

<body>

  <main class="conteudo">

    <!-- Sol com os planetas -->
    <div class="sistema-solar" aria-hidden="true">
      <div class="anel"></div>
      <div class="anel media"></div>
      <div class="anel interna"></div>

      <div class="trajeto externo"><span></span></div>
      <div class="trajeto media"><span></span></div>
      <div class="trajeto interna"><span></span></div>

      <div class="sol"></div>
    </div>

    <!-- Formulário -->
    <div class="posicaoforms">
      <div class="logo">🪐 Solar<span>Control</span></div>
      <h2>Entrar</h2>
      <p class="sub">Bem-vindo de volta. Acesse o painel da sua usina.</p>

      <!-- O action fica vazio "" para enviar os dados para este próprio arquivo PHP -->
      <form action="" method="post" class="formulario">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" placeholder="nome@empresa.com" required>

        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" placeholder="••••••••" minlength="6" required>

        <div class="linha">
          <label><input type="checkbox" name="lembrar" checked> Lembrar-me</label>
          
        </div>

        <button type="submit">Entrar</button>

        <?php if (!empty($mensagem_erro)): ?>
            <p class="erro-login"><?php echo $mensagem_erro; ?></p>
        <?php endif; ?>
      </form>

      <p class="sub centro">Não tem conta? <a href="cadastro.php">Criar conta</a></p>
    </div>

  </main>

</body>
</html>
