<?php
include '../../db_connect.php';
// recebe id do serviço da url
$id_servico = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_servico > 0) {
    try {
        $sql = "DELETE FROM servicos_disponiveis WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id_servico, PDO::PARAM_INT);
        $stmt->execute();
        
        // verifica se alguma linha foi afetada para dar o feedback correto
        if ($stmt->rowCount() > 0) {
            $msg = urlencode('Serviço removido com sucesso!');
            $status = 'removido';
        } else {
            $msg = urlencode('Nenhum serviço encontrado com este ID.');
            $status = 'erro';
        }

    } catch (\PDOException $e) {
        // em caso de erro do banco de dados
        $msg = urlencode("Erro ao tentar remover o serviço: " . $e->getMessage());
        $status = 'erro';
    }
} else {
    $msg = urlencode('ID de serviço inválido para remoção.');
    $status = 'erro';
}

// redireciona de volta para a lista do ADM com a mensagem de status
header('Location: ServicoDispoAdm.php?status=' . $status . '&msg=' . $msg);
exit;
?>