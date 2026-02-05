
<?php
// define contexto e página ativa para o menu lateral
$context_path = 'Adamastor';
$activePage = 'feedback'; 

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

// busca média total de avaliações
$sql_media = "SELECT AVG(nota) AS media_total FROM avaliacoes";
try {
    $stmt_media = $pdo->query($sql_media);
    $media = $stmt_media->fetch(PDO::FETCH_ASSOC);
    $media_total = $media['media_total'] ?? 0;
    // formata a média para ter uma casa decimal
    $media_formatada = number_format($media_total, 1);
} catch (PDOException $e) {
    $media_total = 0;
    $media_formatada = "N/A";
}


// busca avaliações e nomes dos clientes
$sql_avaliacoes = "SELECT 
                    a.nota, 
                    a.comentario, 
                    DATE_FORMAT(a.data_avaliacao, '%d de %M') AS data_formatada,
                    c.nome AS nome_cliente  
                    FROM avaliacoes a 
                    JOIN clientes c ON a.id_cliente = c.id_cliente 
                    ORDER BY a.data_avaliacao DESC";

try {
    $stmt_avaliacoes = $pdo->query($sql_avaliacoes);
    $avaliacoes = $stmt_avaliacoes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $avaliacoes = [];
}

// função para exibir as estrelas
function exibirEstrelas($nota) {
    $html = '';
    // a nota é puxada arredondada
    $estrelas_cheias = round($nota);
    for ($i = 1; $i <= 5; $i++) {
        $class = ($i <= $estrelas_cheias) ? 'filled' : '';
        $html .= "<span class='star-card {$class}'>★</span>";
    }
    return $html;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADM | Avaliações</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Livvic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Sidebar/sidebar.css">
    <link rel="stylesheet" href="avaliacao.css"> 
    
</head>
<body>

    <div class="d-flex"> 
        <?php include '../Sidebar/sidebar.php'; ?>
        <main class="main-content">
              
              <h1 class="page-header-title">Avaliações</h1>

              <div class="page-content-wrapper">
                  
                  <div class="rating-section">
                      <h2 class="rating-title">Pontos Totais</h2>
                      <div class="rating-score"><?= $media_formatada ?></div> 
                      <div class="rating-stars">
                          <?= exibirEstrelas($media_total) ?>
                      </div>
                      
                      <?php if (count($avaliacoes) == 0): ?>
                          <p style="text-align: center; margin-top: 10px; color: #555;">Ainda não há avaliações para exibir.</p>
                      <?php endif; ?>
                  </div>

                  <div class="simple-quad-grid">
                      
                     <?php 
                     // loop para exibir a avaliação salva
                     if (count($avaliacoes) > 0):
                         foreach ($avaliacoes as $avaliacao): 
                     ?>
                     
                     <div class="grid-box">
                         <img src="../../imagens/avatar.png" alt="Avatar" class="avatar"> 
                         <div class="review-content">
                             <div class="review-header">
                                 <h3 class="review-name"><?= htmlspecialchars($avaliacao['nome_cliente']) ?></h3>
                                 <div class="review-meta"><?= htmlspecialchars($avaliacao['data_formatada']) ?></div>
                             </div>
                             <div class="review-stars">
                                 <?= exibirEstrelas($avaliacao['nota']) ?>
                             </div>
                             <p class="review-text"><?= nl2br(htmlspecialchars($avaliacao['comentario'])) ?></p>
                         </div>
                     </div>

                     <?php 
                         endforeach; 
                     endif; 
                     ?>
                  </div>
              </div>
          </main>
    </div>
</body>
</html>