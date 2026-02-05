<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: fazeravaliacao.php?feedback=erro_validacao");
    exit();
}

$cliente_id_sessao = $_SESSION['user_id'];
include '../../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_avaliacao'])) {
    
    $cliente_id_form = $_POST['cliente_id'] ?? null;
    $nota = $_POST['nota'] ?? 0;
    $comentario = $_POST['comentario'] ?? '';
    
    // verifica id do usuario logado
    if ($cliente_id_form != $cliente_id_sessao) {
        header("Location: fazeravaliacao.php?feedback=erro_validacao");
        exit();
    }
    
    // verifica se od do cliente existe no bd
    $sql_check = "SELECT id_cliente FROM clientes WHERE id_cliente = :cliente_id";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->bindParam(':cliente_id', $cliente_id_form, PDO::PARAM_INT);
    $stmt_check->execute();
    
    if ($stmt_check->rowCount() == 0) {
        header("Location: fazeravaliacao.php?feedback=erro_cliente_inexistente");
        exit();
    }
    
    if (!$cliente_id_form || $nota < 1 || $nota > 5) { 
        header("Location: fazeravaliacao.php?feedback=erro_validacao");
        exit();
    }

    // insere no banco
    $sql = "INSERT INTO avaliacoes (id_cliente, nota, comentario, data_avaliacao) 
             VALUES (:cliente_id, :nota, :comentario, NOW())";
    
    try {
        $stmt = $pdo->prepare($sql);
        
        $stmt->bindParam(':cliente_id', $cliente_id_form, PDO::PARAM_INT); 
        $stmt->bindParam(':nota', $nota, PDO::PARAM_INT);             
        $stmt->bindParam(':comentario', $comentario, PDO::PARAM_STR); 

        if ($stmt->execute()) {
            // redireciona de volta para a página de avaliação com mensagem de sucesso
            header("Location: fazeravaliacao.php?feedback=sucesso");
            exit();
        } else {
            header("Location: fazeravaliacao.php?feedback=erro_db");
            exit();
        }
        
    } catch (PDOException $e) {
        header("Location: fazeravaliacao.php?feedback=erro_db");
        exit();
    }
} else {
    header("Location: fazeravaliacao.php");
    exit();
}