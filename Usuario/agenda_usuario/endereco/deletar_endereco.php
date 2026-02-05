<?php
include_once(__DIR__ . "../..//../../db_connect.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_endereco'])) {
    $id_endereco = intval($_POST['id_endereco']);

    // verifica se a conexão PDO está funcionando
    if (!isset($pdo)) {
        echo json_encode(['success' => false, 'error' => 'Conexão com banco de dados não disponível']);
        exit;
    }

    $sql = "DELETE FROM enderecos_cliente WHERE id_endereco = ?";
    $stmt = $pdo->prepare($sql);

    $response = [];

    try {
        if ($stmt->execute([$id_endereco])) {
            $response['success'] = true;
        } else {
            $response['success'] = false;
            $response['error'] = "Erro ao excluir endereço";
        }
    } catch (PDOException $e) {
        $response['success'] = false;
        $response['error'] = "Erro no banco de dados: " . $e->getMessage();
    }

    echo json_encode($response);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Requisição inválida']);
?>