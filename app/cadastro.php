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
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" placeholder="nome@empresa.com" required>

            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" placeholder="••••••••" minlength="6" required>

            <button type="submit">Criar</button>
        </form>

        <?php if ($_SERVER["REQUEST_METHOD"] === "POST"): ?>
            <p class="mensagem">
                <?= ($usuario ?? false) ? "Pode entrar" : "Senha ou login não atende aos requisitos" ?>
            </p>
        <?php endif; ?>


        <div class="paivoltar">
    <a href="login.php" class="botao-voltar">
        <span aria-hidden="true">←</span>
        Voltar para o login
    </a>
</div>


</body>
</html>
