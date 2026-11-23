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
    <link rel="stylesheet" href="CSS/login.css">
</head>

<body>
    <main class="conteudo-principal">


        <nav class="navegacao">
            <div class="links-navegacao">

                <button class="botao-navegacao">
                    <a href="#login">Login</a>
                </button>

                <button class="botao-navegacao">
                    <a href="#Sobre">Sobre</a>
                </button>

                <button class="botao-navegacao">
                    <a href="#Preferencia">Preferência</a>
                </button>

                <button class="botao-navegacao">
                    <a href="#Equipe">Equipe</a>
                </button>

                <button class="botao-navegacao">
                    <a href="#Objetivos">Objetivos</a>
                </button>

            </div>
        </nav>


        <section class="secao-inicial" id="login">

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

            <div class="painel-login">
                <div class="logo">SolarControl</div>


                <h2>Entrar</h2>

                <p class="texto-auxiliar">
                    Bem-vindo de volta. Acesse o painel da sua usina.
                </p>

                <form method="POST" class="formulario-login">

                    <label for="email">E-mail</label>

                    <input class="campos" type="email" id="email" name="email" placeholder="nome@empresa.com" required>

                    <label for="senha">Senha</label>

                    <input class="campos" type="password" id="senha" name="senha" placeholder="••••••••" minlength="6"
                        required>

                    <button class="entrar" type="submit">Entrar</button>

                </form>

                <p class="texto-auxiliar texto-centralizado">
                    Não tem conta?
                    <a href="cadastro.php">Criar conta</a>
                </p>

                <p><?php echo $mensagem_erro; ?> </p>

            </div>

        </section>

        <section class="secao-sobre" id="Sobre">
            <div class="textos-secao-sobre">
                <div class="textodefundo">
                    <h1>SolarControl</h1>
                </div>
                <h1 class="titulo-secao">
                    O que é a<span class="destaque-verde"> SolarControl?</span>
                    <hr class="divisao">
                </h1>

                <div class="digitando">
                    Mais controle para<br>
                    um <span class="destaque-verde">futuro sustentável.</span>
                </div>
                <hr class="divisao2">
                </h2>
                <p>
                    O Solar Control reúne as informações mais importantes do seu sistema fotovoltaico<br> em uma
                    experiência simples e intuitiva. Acompanhe a geração, entenda<br> o consumo e veja como sua energia
                    está performando.
                </p>
            </div>
            <div class="secao-sobre-img">
                <img src="imagens/painelsolar.png" alt="Painel Solar">
            </div>
        </section>


        <section class="secao-destaque" id="Preferencia">
            <div class="mae-secao-destaque">
                <div class="img-secao-destaque">
                    <img src="imagens/porqueasolar.png" alt="Imagem teste">
                    <hr class="divisao3">
                    <h1 class="titulo-abaixo">Transformamos dados em <span class="branco">decisões inteligentes</span>
                    </h1>
                    <p class="descricao-imagem">
                        ajudando você a aproveitar melhor sua energia solar.
                    </p>
                </div>
                <div class="texto-secao-destaque-titulo">
                    <h1 class="verdedestaqueforte">Clean,</h1>
                    <h1 class="verdedestaque">Energy,</h1>
                    <h1>Smart &</h1>
                    <h1>Saving</h1>
                </div>
            </div>
        </section>


        <section class="equipe" id="Equipe">

            <div class="titulo-container">
                <h1 class="titulo-secao">
                    Nossa <span class="destaque-verde">Equipe</span>
                </h1>

                <h2>Pessoas que impulsionam o futuro da energia.</h2>
            </div>

            <div class="cards-equipe">

                <div class="card-equipe">
                    <img src="imagens/pessoa1.png" alt="Integrante da equipe">
                    <h3>Nome do integrante</h3>
                    <p>Desenvolvedor</p>
                </div>

                <div class="card-equipe">
                    <img src="imagens/pessoa2.png" alt="Integrante da equipe">
                    <h3>Nome do integrante</h3>
                    <p>Designer</p>
                </div>

                <div class="card-equipe">
                    <img src="imagens/pessoa3.png" alt="Integrante da equipe">
                    <h3>Nome do integrante</h3>
                    <p>Analista</p>
                </div>

                <div class="card-equipe">
                    <img src="imagens/pessoa4.png" alt="Integrante da equipe">
                    <h3>Nome do integrante</h3>
                    <p>Gestor de projetos</p>
                </div>

            </div>

        </section>





        <section class="secao-objetivos" id="Objetivos">

            <h1 class="titulo-secao">
                <hr class="divisao3">
                <div class="texto-principal">
                    <span class="destaque-verde">NOSSO PROPÓSITO</span>
                </div>
                <br>
                <div class="container-apresentacao">
                    <span class="branco">NOSSOS</span><span class="destaque-verde"> OBJETIVOS</span>
                    <br>
                    <span class="branco-espacado">Tecnologia e sustentabilidade trabalhando juntas para transformar o
                        futuro.</span>
                </div>
            </h1>
            <div class="cards-objetivos">

                <div class="card-objetivo">
                    <div class="icone">
                    </div>
                    <h3>Energia Sustentável</h3>
                    <p>Incentivar o uso de energia limpa, contribuindo para um futuro mais sustentável.</p>
                </div>

                <div class="card-objetivo">
                    <div class="icone">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <h3>Economia Inteligente</h3>
                    <p>Reduzir gastos com energia elétrica por meio de soluções fotovoltaicas.</p>
                </div>

                <div class="card-objetivo">
                    <div class="icone">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <h3>Monitoramento Eficiente</h3>
                    <p>Facilitar o acompanhamento da produção e do desempenho energético.</p>
                </div>

                <div class="card-objetivo">
                    <div class="icone">
                    </div>
                    <h3>Inovação Tecnológica</h3>
                    <p>Desenvolver soluções modernas que aproximem as pessoas da energia renovável.</p>
                </div>

            </div>


        </section>

        <footer class="rodape">


            <div class="rodape-marca">
                <span class="destaque-verde">SolarControl</span>
                <span class="destaque-verde">© 2026 by Solar Control</span>

            </div>

        </footer>










        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
        <script src="js/ScrollSmoother.min.js"></script>
        <script src="javascript/login.js"></script>
    </main>
</body>

</html>