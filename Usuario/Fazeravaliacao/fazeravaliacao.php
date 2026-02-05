<?php
session_start();

// verifica se cliente esta logado
if (!isset($_SESSION['user_id'])) {
    $cliente_id = null;
    $erro_login = "Usuário não logado";
} else {
    $cliente_id = $_SESSION['user_id'];
    $erro_login = null;
}

include '../../db_connect.php';

// sistema de feedback
$feedback_message = '';
if (isset($_GET['feedback'])) {
    if ($_GET['feedback'] == 'sucesso') {
        $feedback_message = '<div class="alert alert-success">✅ Avaliação enviada com sucesso!</div>';
    } elseif ($_GET['feedback'] == 'erro_validacao') {
        $feedback_message = '<div class="alert alert-danger">❌ ERRO: Selecione uma nota para enviar a avaliação!</div>';
    } elseif ($_GET['feedback'] == 'erro_db') {
        $feedback_message = '<div class="alert alert-danger">❌ ERRO: Não foi possível salvar a avaliação. Tente novamente!</div>';
    } elseif ($_GET['feedback'] == 'erro_cliente_inexistente') {
        $feedback_message = '<div class="alert alert-danger">❌ ERRO: Usuário não encontrado!</div>';
    }
}

if ($cliente_id) {
    $sql_check = "SELECT id_cliente, nome FROM clientes WHERE id_cliente = :cliente_id";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->bindParam(':cliente_id', $cliente_id, PDO::PARAM_INT);
    $stmt_check->execute();
    
    $cliente_existente = $stmt_check->fetch();
    
    if (!$cliente_existente) {
        $erro_login = "Cliente ID $cliente_id não encontrado no banco";
        $cliente_id = null;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Fazer Avaliação</title>
    <link href="https://fonts.googleapis.com/css2?family=Livvic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Sidebar/sidebar.css">
    <link rel="stylesheet" href="fazeravaliacao.css"> 
</head>
<body>
<div class="d-flex">
    
    <?php include '../Sidebar/sidebar.php'; ?>


    <main class="main-content">
        
        <header class="main-header">
            <a href="../Avaliacao_usuario/indexusuario.php" class="back-button">
            <img src="../../imagens/voltar.png" alt="Voltar" width="20" height="20">
            <span class="back-text">Voltar</span>
        </a>
            <h1>Fazer Avaliação</h1> 
        </header>
        
        <div class="evaluation-full-area">
            <?php if ($erro_login): ?>
                <!-- mensagem erro login -->
                <div class="login-error">
                    <h3>❌ Erro: <?= $erro_login ?></h3>
                    <p>Faça login para poder enviar avaliações.</p>
                    <a href="../../login_login.php">Fazer Login</a>
                </div>
            <?php else: ?>
                <?= $feedback_message ?>
                
                <!-- formulario -->
                <form method="POST" action="processa_avaliacao.php">
                    <input type="hidden" name="cliente_id" value="<?= $cliente_id ?>"> 
                    <input type="hidden" id="nota_avaliacao" name="nota" value="0" required>

                    <div class="rating-box-compact-center">
                        <div class="rating-stars-large">
                            <span class="star" data-rating="1">★</span>
                            <span class="star" data-rating="2">★</span>
                            <span class="star" data-rating="3">★</span>
                            <span class="star" data-rating="4">★</span>
                            <span class="star" data-rating="5">★</span>
                        </div>

                        <button type="submit" name="submit_avaliacao" class="action-button-save">SALVAR</button>
                    </div>

                    <div class="comment-input-area">
                        <textarea name="comentario" placeholder="Escreva seu comentário aqui..."></textarea>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
    // seleciona todas as estrelas com a classe
    document.querySelectorAll('.star').forEach(star => {
        star.addEventListener('click', function() {
            
            // captura da Nota Escolhida
            const rating = this.getAttribute('data-rating');
            
            // loop de atualização visual
            document.querySelectorAll('.star').forEach(s => {
                const sRating = s.getAttribute('data-rating');
                s.classList.toggle('filled', sRating <= rating); 
            }); 
            
            // define o valor do campo de formulário 'nota_avaliacao' para a nota escolhida, 
            document.getElementById('nota_avaliacao').value = rating;
        });
    });
</script>
</body>
</html>