<?php
session_start();

$baseSidebar = '../Sidebar/';
$baseImages = '../Sidebar/imagens/';

// URLs absolutas para funcionar de qualquer lugar
$urlPerfil = '/Site/Adm/Usuario/perfil.php';
$urlPagamentos = '/Site/Adm/Usuario/indexpagamento.php';
$urlServicos = '/Site/Adm/Servicos/ServicoDispoAdm.php'; 
$urlAgenda = '/Site/Adm/agenda_adm/agenda_visualizar.php';
$urlFeedback = '/Site/Adm/Usuario/feedback.php';
$urlChat = '/Site/Adm/Usuario/chat.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADM | Meu Perfil</title>
    <link href="https://fonts.googleapis.com/css2?family=Livvic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="perfil_adm.css">
    <link rel="stylesheet" href="../Sidebar/sidebar.css">
</head>
<body>
    <div class="d-flex">
        <?php include '../Sidebar/sidebar.php'; ?>

        <main class="perfil-page main-content">
            <div class="main-header">
                <h1>Meu Perfil</h1>
                <div class="logout-container">
                    <form action="../../logout.php" method="POST">
                        <button type="submit" class="btn-sair">Sair</button>
                    </form>
                </div>
            </div>

            <div class="page-content">
                <div class="perfil-card">
                    <div class="saudacao-perfil">
                        <img src="../../imagens/avatar.png" alt="Foto de Perfil" class="perfil-avatar">
                        <span class="perfil-nome">
                            Olá, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Administrador'); ?>!
                        </span>
                    </div>

                    <ul class="opcoes-perfil">
                        <li class="opcao-item">
                            <a href="relatorio.php">Visualizar relatórios</a>
                            <img src="../../imagens/seta.png" alt="Seta" class="seta-icone-img">
                        </li>
                        <li class="opcao-item">
                            <a href="../Avaliacao_adm/avaliacao.php">Visualizar avaliações</a>
                            <img src="../../imagens/seta.png" alt="Seta" class="seta-icone-img">
                        </li>
                        <li class="opcao-item">
                            <a href="../servicos_adm/AdicionarServico.php">Adicionar serviços</a>
                            <img src="../../imagens/seta.png" alt="Seta" class="seta-icone-img">
                        </li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
