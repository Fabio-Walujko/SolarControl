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
       
            $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (:nome, :email, :senha)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':nome' => $nome, ':email' => $email, ':senha' => $senha]);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário de Cadastro</title>
    <link rel="stylesheet" href="CSS/cadastro.css">
</head>
<body>

    <div class="posicaoforms">
        <div class="logo">🪐 Solar<span>Control</span></div>
        <h2>Criar conta</h2>
        <p class="sub">Preencha o formulário para criar sua conta.</p>

        <form action="" method="POST" class="formulario">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" placeholder="nome completo" required>

            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" placeholder="nome@empresa.com" required>

            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" placeholder="••••••••" minlength="6" required>

            <button type="submit">Criar</button>
        </form>


        <div class="paivoltar">
    <a href="login.php" class="botao-voltar">
        <span aria-hidden="true">←</span>
        Voltar para o login
    </a>
    <p><?php  echo $mensagem_erro; ?></php></p>
</div>


</body>
</html>
