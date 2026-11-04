<?php
require_once 'loginTeste.php';


$email = $_POST['email'];
$senha = $_POST['senha'];

if ($email == "teste@gmail.com" && $senha == "teste123") {
    header('location: index.php');
    exit;
}
else {
    print 'Email ou senha invalido';
    
}
