<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="processaLogin.php" method="POST" style="margin-top: 2rem; display: flex; flex-direction: column; gap: 1rem; max-width: 300px;">
    <div>
        <label for="email" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">E-mail:</label>
        <input type="email" id="email" name="email" required style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: .25rem;">
    </div>
    
    <div>
        <label for="senha" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Senha:</label>
        <input type="password" id="senha" name="senha" required style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: .25rem;">
    </div>

    <button type="submit" style="padding: 0.75rem; background-color: #007bff; color: white; border: none; border-radius: .25rem; cursor: pointer; font-weight: bold;">
        Entrar
    </button>
</form>
</body>
</html>