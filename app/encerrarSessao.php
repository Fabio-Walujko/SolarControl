<?php
require_once __DIR__ . '/init.php';

encerrarSessaoUsuario();
header('Location: login.php');
exit;
?>