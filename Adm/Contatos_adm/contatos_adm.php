<?php
include '../../db_connect.php'; 

$sql = "SELECT id_cliente, nome, telefone, email, cidade, estado FROM clientes WHERE tipo_usuario = 'cliente'";
$stmt = $pdo->query($sql);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nomeAdmin = $_SESSION['user_name'] ?? 'Adamastor';

$possiblePaths = [
    '../Sidebar/',
    '../../Sidebar/',
];

$baseSidebar = '';
foreach ($possiblePaths as $path) {
    if (file_exists($path . 'sidebar.php')) {
        $baseSidebar = $path;
        break;
    }
}
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Contatos - Clientes</title>
    <link rel="stylesheet" href="<?= $baseSidebar ?>sidebar.css">
    <link rel="stylesheet" href="contatos_adm.css"> 

</head>
<body>
    <?php include_once($baseSidebar . 'sidebar.php'); ?>
    <main>
        <h1>Contatos dos Clientes</h1>

        <?php if (count($result) > 0): ?>
            <table>
                <tr>
                    <th>Nome</th>
                    <th>Telefone</th>
                    <th>E-mail</th>
                    <th>Cidade</th>
                    <th>Ação</th>
                </tr>

                <?php foreach ($result as $row):
                    // remove caracteres não numéricos do telefone
                    $telefoneLimpo = preg_replace('/\D/', '', $row['telefone']);
                    $mensagem = urlencode("Olá " . $row['nome'] . ", aqui é o " . $nomeAdmin . "!");
                    $linkWhatsApp = "https://wa.me/55" . $telefoneLimpo . "?text=" . $mensagem;
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['nome']) ?></td>
                    <td><?= htmlspecialchars($row['telefone']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['cidade']) ?> - <?= htmlspecialchars($row['estado']) ?></td>
                    <td>
                        <a class="whatsapp-btn" href="<?= $linkWhatsApp ?>" target="_blank">Conversar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>Nenhum cliente encontrado.</p>
        <?php endif; ?>
    </main>
</body>
</html>
