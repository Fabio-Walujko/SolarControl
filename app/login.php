<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/init.php';
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
                $_SESSION['usuario_logado'] = true;
                $_SESSION['email'] = $email;
                $_SESSION['nome'] = $usuario['nome'];
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
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SolarControl</title>
    <link rel="stylesheet" href="CSS\login.css">
</head>

<body>
    <main class="conteudo-principal">
        <nav class="navegacao">
            <div class="barra-navegacao">
                <div class="links-navegacao">

                    <button class="botao-navegacao">
                        <a href="#javascript/login">javascript/login</a>
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

        <section class="secao-inicial" id="javascript/login">

            <div class="texto-introducao">

                <h1 class="titulo-secao">
                    Controle<br>
                    <span class="destaque-verde">
                        inteligente<br>Energia sustentável
                    </span>
                </h1>

                <h2>
                    Monitore sua produção de energia solar,<br>
                    acompanhe seu consumo e<br>
                    tenha controle total do seu sistema em um só lugar.
                </h2>
            </div>

            <div class="painel-javascript/login">
                <div class="logo">SolarControl</div>

                <h2>Entrar</h2>

                <p class="texto-auxiliar">
                    Bem-vindo de volta. Acesse o painel da sua usina.
                </p>

                <form method="POST" class="formulario-javascript/login">

                    <label for="email">E-mail</label>

                    <input class="campos" type="email" id="email" name="email" placeholder="nome@empresa.com" required>

                    <label for="senha">Senha</label>

                    <input class="campos" type="password" id="senha" name="senha" placeholder="••••••••" minlength="6"
                        required>

                    <div class="linha-opcoes">

                        <label>
                            <input type="checkbox" name="lembrar" checked>
                            Lembrar-me
                        </label>

                        <a href="esqueci-senha.php">Esqueci a senha</a>

                    </div>

                    <button class="entrar" type="submit">Entrar</button>

                </form>

                <p class="texto-auxiliar texto-centralizado">
                    Não tem conta?
                    <a href="cadastro.php">Criar conta</a>
                </p>

            </div>

        </section>

        <section class="secao-sobre" id="Sobre">
            <div class="textos-secao-sobre">
                <h1 class="titulo-secao">
                    O que é a<span class="destaque-verde"> SolarControl?</span>
                    <hr class="divisao">
                </h1>

                <div class="digitando">
                    Mais controle para<br>
                    um <span class="destaque-verde">futuro sustentável.</span>
                </div>
                </h2>
                <p>
                    O Solar Control reúne as informações mais importantes do seu sistema fotovoltaico<br> em uma
                    experiência simples e intuitiva. Acompanhe a geração, entenda<br> o consumo e veja como sua energia
                    está performando.
                </p>
            </div>
            <div>
                <img src="">
            </div>
        </section>

        <section class="secao-destaque" id="Porque">

            <div class="titulo-container">
                <h1 class="titulo-secao">
                    Porque a<span class="destaque-verde"> SolarControl?</span>
                </h1>
            </div>

        </section>

        <section class="Equipe" id="Preferencia">

            <div class="titulo-container">
                <h1 class="titulo-secao">
                    Nossa<span class="destaque-verde"> Equipe</span>
                </h1>
            </div>

        </section>

        <section class="secao-objetivos" id="Objetivos">

            <h1 class="titulo-secao">
                Nossos<span class="destaque-verde"> Objetivos</span>
            </h1>

            <h2></h2>

        </section>








        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
        <script src="js/ScrollSmoother.min.js"></script>
        <script src="javascript/login.js"></script>
    </main>
</body>

</html>