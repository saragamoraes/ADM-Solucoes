<?php
session_start();

// verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil do Usuário</title>

    <link href="https://fonts.googleapis.com/css2?family=Livvic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../Sidebar/sidebar.css">

    <link rel="stylesheet" href="perfil.css">
</head>
<body>
    <div class="d-flex">
        <?php include '../Sidebar/sidebar.php'; ?>
        <main class="main-content">
            <header class="main-header">
                <h1>Meu Perfil</h1>
                <!-- botão de sair -->
                <div class="logout-container">
                    <form action="../../logout.php" method="POST">
                        <button type="submit" class="btn-sair">Sair</button>
                    </form>
                </div>
            </header>
            <div class="page-content">
                <div class="perfil-card">
                    <div class="saudacao-perfil">
                        <img src="../../imagens/avatar.png" alt="Foto de Perfil" class="perfil-avatar">
                        <span class="perfil-nome">Olá, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?>!</span>
                    </div>
                    <ul class="opcoes-perfil">
                        <li class="opcao-item">
                            <a href="meus_agendamentos.php">Gerenciar agendamentos</a>
                            <img src="../../imagens/seta.png" alt="Seta" class="seta-icone-img">
                        </li>                       
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>