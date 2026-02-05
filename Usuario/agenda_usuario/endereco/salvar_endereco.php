<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login_login.php");
    exit();
}

// pega id do cliente logado
$cliente_logado_id = $_SESSION['user_id'];

include_once(__DIR__ . "/../../../db_connect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // coleta dos dados do formulário
    $cep = $_POST['cep'] ?? ''; 
    $rua = $_POST['rua'] ?? '';
    $numero = $_POST['numero'] ?? '';
    $referencia = $_POST['referencia'] ?? '';
    $bairro = $_POST['bairro'] ?? '';
    $cidade = $_POST['cidade'] ?? '';
    $estado = $_POST['estado'] ?? '';
    
    $complemento = $_POST['complemento'] ?? ''; 

    $sql = "
        INSERT INTO enderecos_cliente 
            (id_cliente, cep, rua, numero, bairro, cidade, estado, referencia, complemento) 
        VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([
        $cliente_logado_id, 
        $cep, 
        $rua, 
        $numero, 
        $bairro, 
        $cidade, 
        $estado, 
        $referencia, 
        $complemento
    ])) {
        // redireciona corretamente para a agenda
        header("Location: ../agenda.php?status=endereco_salvo");
        exit;
    } else {
        // apenas para debug
        error_log("Erro ao salvar endereço: " . $stmt->errorInfo()[2]);
        echo "Erro ao salvar endereço: " . $stmt->errorInfo()[2]; // Mostra o erro para você
    }
} else {
    // redireciona de volta
    header("Location: ../agenda.php");
    exit;
}