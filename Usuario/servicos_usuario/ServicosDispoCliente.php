<?php
include '../../db_connect.php';

// array para armazenar os serviços e variável de erro
$servicos = [];
$erro = '';

try {
    // busca ID, nome e imagem
    $sql = "SELECT id, nome, imagem 
            FROM servicos_disponiveis 
            WHERE ativo = 1 
            ORDER BY nome ASC"; 
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (\PDOException $e) {
    $erro = "Erro ao carregar os serviços: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serviços Disponíveis</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Livvic:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../Sidebar/sidebar.css">
    <link rel="stylesheet" href="ServicoDispoClienteCss.css"> 
    
</head>
<body>
    <div class="d-flex">
        <?php include '../Sidebar/sidebar.php'; ?>
         <main class="main-content">
            <h1 class="title-page">Serviços Disponíveis</h1>
            
            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger mx-auto" role="alert" style="max-width: 900px;"><?php echo $erro; ?></div>
            <?php endif; ?>
            
            <div class="cards-container">
                <div class="row g-4 justify-content-center">
                    
                    <?php if (count($servicos) > 0): ?>
                        <?php foreach ($servicos as $servico): 
                            // pega o ID do serviço
                            $id_servico = $servico['id']; 
                            $link_servico = 'DetalheServico.php?id=' . $id_servico; 
                            
                            $imagem_servico = htmlspecialchars($servico['imagem'] ?? 'default.png');
                        ?>
                        <div class="col-6 col-md-4 col-lg-4">
                            <a href="<?php echo $link_servico; ?>" style="text-decoration:none; color:inherit;">
                                <div class="card-service">
                                    <img src="../../imagens/<?php echo $imagem_servico; ?>" alt="<?php echo htmlspecialchars($servico['nome']); ?>">
                                    <p><?php echo htmlspecialchars($servico['nome']); ?></p>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center mt-5">
                            <p class="text-muted">Nenhum serviço disponível no momento. Verifique se há serviços ativos na tabela `servicos_disponiveis`.</p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>