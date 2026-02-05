<?php
include '../../db_connect.php';

$baseSidebar = '../Sidebar/';
$baseImages = '../Sidebar/imagens/';

// URLs absolutas para funcionar de qualquer lugar
$urlPerfil = '/Site/Adm/Usuario/perfil.php';
$urlPagamentos = '/Site/Adm/Usuario/indexpagamento.php';
$urlServicos = '/Site/Adm/Servicos/ServicoDispoAdm.php';
$urlAgenda = '/Site/Adm/agenda_adm/agenda_visualizar.php';
$urlFeedback = '/Site/Adm/Usuario/feedback.php';
$urlChat = '/Site/Adm/Usuario/chat.php';

$servicos = [];
$erro_bd = '';

try {
    // consulta ao banco de dados para buscar todos os serviços
    $sql = "SELECT id, nome, imagem, link_usuario 
            FROM servicos_disponiveis 
            ORDER BY nome ASC"; 
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (\PDOException $e) {
    // erro
    $erro_bd = "Erro ao carregar os serviços do BD. Detalhe: " . $e->getMessage();
}

// logica de exibição de mensagens de feedbacks
$alerta = '';
$status = $_GET['status'] ?? '';
$mensagem_personalizada = $_GET['msg'] ?? '';

if ($status) {
    switch ($status) {
        case 'adicionado':
            $tipo_alerta = 'success';
            $texto_alerta = 'Serviço adicionado com sucesso!';
            break;
        case 'editado':
            $tipo_alerta = 'success';
            $texto_alerta = 'Serviço editado com sucesso!';
            break;
        case 'removido':
            $tipo_alerta = 'success';
            $texto_alerta = $mensagem_personalizada ? urldecode($mensagem_personalizada) : 'Serviço removido com sucesso!';
            break;
        case 'erro':
            $tipo_alerta = 'danger';
            $texto_alerta = $mensagem_personalizada ? urldecode($mensagem_personalizada) : 'Ocorreu um erro na operação.';
            break;
        default:
            $tipo_alerta = '';
            $texto_alerta = '';
            break;
    }

    if (!empty($tipo_alerta) && !empty($texto_alerta)) {
        $alerta = '<div class="alert alert-' . $tipo_alerta . ' alert-dismissible fade show" role="alert" style="max-width: 900px; margin: 20px auto;">' .
                  htmlspecialchars($texto_alerta) .
                  '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' .
                  '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADM | Serviços Disponíveis</title> 
    
    <link href="https://fonts.googleapis.com/css2?family=Livvic:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="../Sidebar/sidebar.css">
    
    <link rel="stylesheet" href="ServicoDispoAdminCss.css"> 
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="d-flex">
        <?php include '../Sidebar/sidebar.php'; ?>

        <main class="main-content">
            <h1 class="title-page">Gerenciamento de Serviços</h1>
            
            <?php 
            // exibe alerta de estados
            echo $alerta;
            ?>
            
            <?php if ($erro_bd): ?>
                <div class="alert alert-danger mx-auto" role="alert" style="max-width: 900px;"><?php echo $erro_bd; ?></div>
            <?php endif; ?>
            
            <div class="cards-container">
                <div class="row g-4 justify-content-center">
                    
                    <?php if (count($servicos) > 0): ?>
                        <?php foreach ($servicos as $servico): 
                            $id_servico = (int)($servico['id'] ?? 0);
                            $imagem_servico = htmlspecialchars($servico['imagem'] ?? 'default.png');
                        ?>
                        <div class="col-6 col-md-4 col-lg-4">
                            <div class="card-service card-admin-item">
                                <img src="../../imagens/<?php echo $imagem_servico; ?>" alt="<?php echo htmlspecialchars($servico['nome']); ?>">
                                <p><?php echo htmlspecialchars($servico['nome']); ?></p>
                                
                                <div class="admin-actions">
                                    
                                    <a href="EditarServico.php?id=<?php echo $id_servico; ?>" class="btn-action edit-action">Editar</a>
                                    
                                    <a href="RemoverServico.php?id=<?php echo $id_servico; ?>" class="btn-action remove-action" 
                                       onclick="return confirm('Tem certeza que deseja remover o serviço: <?php echo htmlspecialchars($servico['nome']); ?>? Esta ação é irreversível.');">
                                        Remover
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <div class="col-6 col-md-4 col-lg-4">
                        <a href="AdicionarServico.php" class="card-link-add">
                            <div class="card-service card-add-new">
                                <i class="fa-solid fa-circle-plus"></i>
                                <p>Adicionar Novo</p>
                            </div>
                        </a>
                    </div>
                    
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>