<?php
include("../../db_connect.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// consulta SQL
$sql_agendamentos = "
    SELECT a.id_agendamento, a.data_agendamento, a.hora_inicio, a.hora_fim,
           sd.nome AS nome_servico,
           i.preco_no_agendamento
    FROM agendamentos a
    LEFT JOIN itens_agendamento i ON a.id_agendamento = i.id_agendamento
    LEFT JOIN servicos_disponiveis sd ON i.id_servico = sd.id
    WHERE a.status = 'finalizado'
    ORDER BY a.data_agendamento DESC, a.hora_inicio ASC
";

try {
    $stmt = $pdo->prepare($sql_agendamentos);
    $stmt->execute();
    
    $servicos = [];
    $saldo_atual = 0;

    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch()) {
            $servicos[] = [
                'id' => $row['id_agendamento'],
                'nome' => $row['nome_servico'] ?? 'Serviço Agendado',
                'data' => date('d/m/Y', strtotime($row['data_agendamento'])),
                'hora_inicio' => $row['hora_inicio'],
                'hora_fim' => $row['hora_fim'],
                'valor' => $row['preco_no_agendamento'] ?? 0
            ];
            $saldo_atual += $row['preco_no_agendamento'] ?? 0;
        }
    }
} catch (PDOException $e) {
    die("Erro na consulta: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADM | Relatório Mensal</title>
    <link rel="stylesheet" href="../Sidebar/sidebar.css">
    <link rel="stylesheet" href="relatorio.css"> 
    <link href="https://fonts.googleapis.com/css2?family=Livvic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="d-flex">
    <?php include '../Sidebar/sidebar.php'; ?>
    <main class="report-main-content">    
        <header class="report-header">
            <h1>Relatórios</h1>
        </header>

        <section class="balance-section">
            <p class="current-balance">Saldo atual: <span>R$<?php echo number_format($saldo_atual, 2, ',', '.'); ?></span></p>
        </section>
        
        <div style="margin-bottom: 20px;">
            <select class="custom-select" id="ordenarSelect">
                <option value="">Ordenar por</option>
                <option value="data">Data</option>
                <option value="valor">Valor</option>
            </select>
        </div>

        <section class="services-container">
            <?php if(count($servicos) > 0): ?>
                <?php foreach ($servicos as $servico): ?>
                    <div class="service-card" data-date="<?php echo date('Y-m-d', strtotime(str_replace('/', '-', $servico['data']))); ?>" data-value="<?php echo $servico['valor']; ?>">
                        <div class="service-card-header">
                            <p>Serviço: <?php echo htmlspecialchars($servico['nome']); ?></p>
                            <span><?php echo htmlspecialchars($servico['data']); ?> <?php echo htmlspecialchars($servico['hora_inicio']); ?> - <?php echo htmlspecialchars($servico['hora_fim']); ?></span>
                        </div>
                        <div class="service-card-body">
                            <p>Valor: R$<?php echo number_format($servico['valor'], 2, ',', '.'); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Nenhum agendamento finalizado encontrado.</p>
            <?php endif; ?>
        </section>
    </main>
</div>

<script>
document.getElementById('ordenarSelect').addEventListener('change', function() {
    const ordenarPor = this.value;
    const container = document.querySelector('.services-container');
    const cards = Array.from(container.querySelectorAll('.service-card'));
    
    if (ordenarPor === 'data') {
        cards.sort((a, b) => {
            const dateA = new Date(a.getAttribute('data-date'));
            const dateB = new Date(b.getAttribute('data-date'));
            return dateB - dateA; // mais recente primeiro
        });
    } else if (ordenarPor === 'valor') {
        cards.sort((a, b) => {
            const valorA = parseFloat(a.getAttribute('data-value'));
            const valorB = parseFloat(b.getAttribute('data-value'));
            return valorB - valorA; // maior valor primeiro
        });
    }
    
    // limpar container e adicionar cards ordenados
    container.innerHTML = '';
    cards.forEach(card => container.appendChild(card));
});
</script>
</body>
</html>