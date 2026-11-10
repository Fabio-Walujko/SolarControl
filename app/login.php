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
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SolarControl</title>
<link rel="stylesheet" href="CSS/login.css">
</head>

<body>
<header>
<nav class="navegacao">
<div class="barra-navegacao">


<div class="links-navegacao">
<button class="botao-navegacao">
<a href="#Login">Login</a>
</button>

<button class="botao-navegacao">
<a href="#Sobre">Sobre</a>
</button>

<button class="botao-navegacao">
<a href="#Porque">Preferencia</a>
</button>

<button class="botao-navegacao">
<a href="#Preferencia">Equipe</a>
</button>

<button class="botao-navegacao">
<a href="#Objetivos">Objetivos</a>
</button>
</div>

</div>
</nav>
</header>

<main class="conteudo-principal">

<section class="secao-inicial" id="#Login">

<div class="texto-introducao">
<h1 class="titulo-secao">Sua Energia Solar,<br><span class="destaque-laranja">em orbita</span></h1>
<h2>A SolarControl une tecnologia e sustentabilidade<br>em uma solução inteligente e eficiente.</h2>
</div>






<div class="painel-login">
<div class="logo">Solar<span class="destaque-laranja">Control</span></div>

<h2>Entrar</h2>

<p class="texto-auxiliar">
Bem-vindo de volta. Acesse o painel da sua usina.
</p>


<form method="post" class="formulario-login">
<label for="email">E-mail</label>

<input type="email" id="email" name="email" placeholder="nome@empresa.com" required>

<label for="senha">Senha</label>
<input type="password" id="senha" name="senha" placeholder="••••••••" minlength="6" required>

<div class="linha-opcoes">

<label>
<input type="checkbox" name="lembrar" checked>
Lembrar-me
</label>

<a href="esqueci-senha.php">Esqueci a senha</a>

</div>

<button type="submit">Entrar</button>

<?php  echo "Ocorreu o seguinte erro: ".$mensagem_erro; ?>

</form>

<p class="texto-auxiliar texto-centralizado">
Não tem conta?
<a href="cadastro.php">Criar conta</a>
</p>

</div>

</section>

<section class="secao-sobre" id="Sobre">
<h1 class="titulo-secao">Sobre<span class="destaque-laranja"> nós</span></h1>
<h2>A SolarControl é uma plataforma desenvolvida para tornar o monitoramento<br> de sistemas
fotovoltaicos mais simples, inteligente e eficiente. Nosso objetivo<br> é conectar tecnologia e
sustentabilidade, oferecendo ferramentas que<br>permitem acompanhar a geração de energia, analisar o
desempenho dos painéis<br> solares e otimizar o consumo energético. Acreditamos em um futuro mais
sustentável,<br> onde a tecnologia transforma a maneira como utilizamos a energia solar.</h2>
</section>


<section class="secao-destaque" id="Porque">
<div class="titulo-container">
<h1 class="titulo-secao">Porque a<span class="destaque-laranja"> SolarControl?</span></h1>
</div>
</section>


<section class="secao-destaque" id="Preferencia">
<div class="titulo-container">
<h1 class="titulo-secao">Nossa<span class="destaque-laranja"> Equipe</span></h1>
</div>
</section>


<section class="secao-objetivos" id="Objetivos">

<h1 class="titulo-secao">Nossos<span class="destaque-laranja"> Objetivos</span></h1>
<h2></h2>
</section>
</main>


</body>

</html>