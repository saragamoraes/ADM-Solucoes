<?php
session_start();

// destrói variáveis da sessão
$_SESSION = [];

// destrói a sessão
session_destroy();

// redireciona para a página de login
header("Location: index.php");
exit();
?>
