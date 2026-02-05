<?php
include '../../db_connect.php';

// recebe o ID do serviço da URL
$id_servico = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$servico = null; 

if ($id_servico > 0) {
    try {
        $sql = "SELECT nome, valor, descricao, descricao_adicional 
                FROM servicos_disponiveis 
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id_servico, PDO::PARAM_INT);
        $stmt->execute();
        $servico = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$servico) {
            die("Serviço não encontrado.");
        }

    } catch (\PDOException $e) {
        die("Erro ao carregar detalhes: " . $e->getMessage());
    }
} else {
    die("ID de serviço inválido.");
}

// define as variáveis que serão usadas no HTML
$titulo_pagina = htmlspecialchars($servico['nome']);
$valor_formatado = number_format($servico['valor'], 2, ',', '.'); 
$descricao_principal = nl2br(htmlspecialchars($servico['descricao'])); 
$descricao_atencao = nl2br(htmlspecialchars($servico['descricao_adicional']));
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Serviço: <?php echo $titulo_pagina; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Livvic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../Sidebar/sidebar.css">
    <link rel="stylesheet" href="ServicoDispoClienteCss.css"> 
    
</head>
<body>
    <div class="d-flex">
        <?php include '../Sidebar/sidebar.php'; ?>
        <main class="main-content">
            <div class="main-content-header">
                <a href="ServicosDispoCliente.php" class="back-button">
                    <img src="../../imagens/voltar.png" alt="Voltar" width="20" height="20">
                    <span class="back-text">Voltar</span>
                </a>
                <h1 class="page-title-detail">Serviços Disponíveis</h1>
            </div>

            <div class="vazamento-detail-container">
                <div class="vazamento-detail-card">
                    <h2 class="card-detail-title"><?php echo $titulo_pagina; ?></h2>
                    
                    <p class="card-detail-text">
                        <?php echo $descricao_principal; ?>
                    </p>
                    
                   <div class="price-info">
                        <img src="../../imagens/etiqueta.png" alt="Etiqueta de Preço" class="price-tag-image">
                        
                        <span class="price-value-small">R$ <?php echo $valor_formatado; ?></span>
                        
                    </div>

                    <div class="attention-image-container">
                        <img src="../../imagens/atencao.png" alt="Atenção" class="attention-image">
                        <p class="attention-label">ATENÇÃO</p> 
                    </div>

                    <p class="card-detail-text attention-text">
                        <?php echo $descricao_atencao; ?>
                    </p>
                    
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>