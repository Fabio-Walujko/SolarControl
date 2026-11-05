<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SolarControlll</title>
  <link rel="stylesheet" href="formulario.css">
</head>

<body>

  <div class="nebulas">
    <div class="nebula nebula--roxa"></div>
    <div class="nebula nebula--violeta"></div>
    <div class="nebula nebula--azul"></div>
  </div>

  
  <main class="conteudo">
    <div class="sistema-solar" >
      <div class="anel"></div>
      <div class="anel media"></div>
      <div class="anel interna"></div>

      <div class="trajeto externo"><span></div>
      <div class="trajeto media"><span></div>
      <div class="trajeto interna"><span></div>

      <div class="sol"></div>
    </div>

        <div class="posicaoforms">
    <div class="logo">🪐 Solar<span>Control</span></div>
    <h2>Entrar</h2>
    <p class="sub">Bem-vindo de volta. Acesse o painel da sua usina.</p>

    <form method="post" class="formulario">
      <label for="email">E-mail</label>
      <input type="email" id="email" name="email" placeholder="nome@empresa.com" required>

      <label for="senha">Senha</label>
      <input type="password" id="senha" name="senha" placeholder="••••••••" minlength="6" required>

      <div class="linha">
        <label><input type="checkbox" name="lembrar" checked> Lembrar-me</label>
        <a href="esqueci-senha.php">Esqueci a senha</a>
      </div>

      <button type="submit">Entrar</button>
    </form>

    <p class="sub centro">Não tem conta? <a href="cadastro.php">Criar conta</a></p>
  </div>

  </main>

</body>
</html>
