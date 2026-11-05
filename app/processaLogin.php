<?php
 
require_once __DIR__ . '/banco/database.php';   


$email = $_POST['email'];
$senha = $_POST['senha'];


if (!empty($email) && !empty($senha)){
            if ($email == "teste@gmail.com" && $senha == "teste123") {
        header('location: index.php');
        exit;
    }
    else {
        print 'Email ou senha invalido';
    
    }

        try {
        $sql = "SELECT * FROM usuarios WHERE email = 'email'";
        
        $stmt = $pdo->prepare($sql);
        
        // 4. Vincula o e-mail digitado ao comando SQL e executa
        $stmt->execute(['email' => $email]);
        
        // 5. Busca o resultado
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // 6. Verifica se encontrou o usuário e se a senha bate
        // (Se a senha estiver criptografada, o ideal é usar: password_verify($senha_entrada, $usuario['senha']))
        if ($usuario && $usuario['senha'] == $senha_entrada) {
            
            // Login com sucesso, redireciona para a index
            header('Location: index.php');
            exit;
            
        } else {
            // E-mail não encontrado ou senha incorreta
            print 'Email ou senha invalido';
        }

    } catch (PDOException $e) {
        print "Erro no banco de dados: " . $e->getMessage();
    }

}



