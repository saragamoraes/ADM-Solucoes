<?php
$host = 'sql113.byetcluster.com'; // servidor de hospedagem
$db   = 'if0_40345141_spigo594_grupo01'; 
$user = 'if0_40345141';              
$pass = 'eazgouwjNCrKolZ';       
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Erro de Conexão: " . $e->getMessage());
}
?>