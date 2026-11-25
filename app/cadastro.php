<?php
require_once __DIR__ . '/init.php';

$mensagem_erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');
    $nome = trim((string) ($_POST['nome'] ?? ''));

    if ($email === '' || $senha === '' || $nome === '') {
        $mensagem_erro = 'Preencha nome, e-mail e senha.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem_erro = 'Informe um e-mail válido.';
    } elseif (strlen($senha) < 6 || strlen($senha) > 20) {
        // A coluna usuarios.senha é VARCHAR(20)
        $mensagem_erro = 'A senha precisa ter entre 6 e 20 caracteres.';
    } else {
        try {
            // Novo usuário entra como "cliente" (padrão da tabela)
            create($pdo, 'usuarios', [
                'nome' => substr($nome, 0, 100),
                'email' => substr($email, 0, 100),
                'senha' => $senha,
            ]);
            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            $mensagem_erro = $e->getCode() === '23000'
                ? 'Já existe uma conta com este e-mail.'
                : 'Não foi possível criar a conta agora. Tente novamente.';
            error_log('Erro no cadastro: ' . $e->getMessage());
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
    <link rel="stylesheet" href="CSS/cadastro.css">
</head>

<body>
    <main class="conteudo-principal">
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
                <h2>Criar Conta</h2>

                <p class="texto-auxiliar">
                    Boas-vindas ao melhor site de controle fotovoltaico!
                </p>

                <form method="POST" class="formulario-login">
                    <label for="nome">Nome</label>
                    <input class="campos" type="text" id="nome" name="nome" placeholder="Seu nome" required>

                    <label for="email">E-mail</label>
                    <input class="campos" type="email" id="email" name="email" placeholder="nome@empresa.com" required>

                    <label for="senha">Senha</label>
                    <input class="campos" type="password" id="senha" name="senha" placeholder="••••••••" minlength="6" maxlength="20" required>

                    <button class="entrar" type="submit">Criar</button>
                </form>

                <p class="texto-auxiliar texto-centralizado">
                    <a class="botao-navegacao" href="login.php">Voltar</a>
                </p>

                <?php if ($mensagem_erro !== ''): ?>
                    <p class="mensagem-erro"><?= h($mensagem_erro) ?></p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="rodape">
        <div class="rodape-marca">
            <span class="destaque-verde">SolarControl</span>
            <span class="destaque-verde">© 2026 by Solar Control</span>
        </div>
    </footer>
</body>

</html>
