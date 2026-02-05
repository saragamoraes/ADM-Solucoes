<?php
// fuso
date_default_timezone_set('America/Sao_Paulo'); 

session_start();

// verifica se cliente esta logado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// pega id do cliente da sessão
$id_cliente_logado = $_SESSION['user_id'];

// verifica se o ID do agendamento foi passado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: meus_agendamentos.php?status=erro_id_nao_informado');
    exit;
}

$id_agendamento = intval($_GET['id']);

include '../../db_connect.php';

// busca dados do agendamento
$sql_agendamento = "
    SELECT 
        a.id_agendamento, a.data_agendamento, a.hora_inicio, a.status,
        a.id_cliente, a.id_endereco,
        c.nome AS nome_cliente, c.telefone,
        c.rua AS rua_cliente, c.numero AS numero_cliente, 
        c.bairro AS bairro_cliente, c.cidade AS cidade_cliente, 
        c.estado AS estado_cliente, c.complemento AS complemento_cliente,
        c.referencia AS referencia_cliente,
        ec.rua AS rua_endereco, ec.numero AS numero_endereco, 
        ec.bairro AS bairro_endereco, ec.cidade AS cidade_endereco, 
        ec.estado AS estado_endereco, ec.complemento AS complemento_endereco,
        ec.referencia AS referencia_endereco
    FROM 
        agendamentos a
    JOIN 
        clientes c ON a.id_cliente = c.id_cliente
    LEFT JOIN 
        enderecos_cliente ec ON a.id_endereco = ec.id_endereco
    WHERE 
        a.id_agendamento = ? AND a.id_cliente = ?
";

$stmt = $pdo->prepare($sql_agendamento);
if (!$stmt) {
    die("Erro no prepare: " . $pdo->errorInfo()[2]);
}

if (!$stmt->execute([$id_agendamento, $id_cliente_logado])) {
    die("Erro no execute: " . $stmt->errorInfo()[2]);
}

$agendamento = $stmt->fetch(PDO::FETCH_ASSOC);

// verifica se o agendamento existe e pertence ao cliente
if (!$agendamento) {
    header('Location: meus_agendamentos.php?status=erro_agendamento_nao_encontrado');
    exit;
}

// busca serviços do agendamento
$sql_servicos = "
    SELECT 
        sd.nome AS nome_servico,
        ia.preco_no_agendamento
    FROM 
        itens_agendamento ia
    JOIN 
        servicos_disponiveis sd ON ia.id_servico = sd.id
    WHERE 
        ia.id_agendamento = ?
";

$stmt_servicos = $pdo->prepare($sql_servicos);
$stmt_servicos->execute([$id_agendamento]);
$servicos = $stmt_servicos->fetchAll(PDO::FETCH_ASSOC);

// calcular valor total
$valor_total = 0;
foreach ($servicos as $servico) {
    $valor_total += (float)$servico['preco_no_agendamento'];
}

// verifica se o agendamento pode ser cancelado
if (!in_array($agendamento['status'], ['Pendente', 'Confirmado'])) {
    header('Location: meus_agendamentos.php?status=erro_agendamento_nao_cancelavel');
    exit;
}

// formatar dados para exibição
$data_formatada = (new DateTime($agendamento['data_agendamento']))->format('d/m/Y');
$hora_formatada = (new DateTime($agendamento['hora_inicio']))->format('H:i');

// definir endereço correto
if ($agendamento['id_endereco'] == $agendamento['id_cliente']) {
    // usa endereço do cliente
    $rua = $agendamento['rua_cliente'];
    $numero = $agendamento['numero_cliente'];
    $bairro = $agendamento['bairro_cliente'];
    $cidade = $agendamento['cidade_cliente'];
    $estado = $agendamento['estado_cliente'];
    $complemento = $agendamento['complemento_cliente'];
    $referencia = $agendamento['referencia_cliente'];
} else {
    // usa endereço da tabela enderecos_cliente
    $rua = $agendamento['rua_endereco'];
    $numero = $agendamento['numero_endereco'];
    $bairro = $agendamento['bairro_endereco'];
    $cidade = $agendamento['cidade_endereco'];
    $estado = $agendamento['estado_endereco'];
    $complemento = $agendamento['complemento_endereco'];
    $referencia = $agendamento['referencia_endereco'];
}

// formatar endereço
$complementoTexto = trim($complemento ?? '');
$complementoParte = $complementoTexto !== '' ? ' - ' . $complementoTexto : '';
$enderecoFormatado = $rua . ', nº ' . $numero . $complementoParte . ' – Bairro ' . $bairro;
$cidadeEstado = $cidade . ', ' . $estado;

// processa o cancelamento POST
$mensagem_erro = '';
$mensagem_sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // atualizar o status do agendamento para "Cancelado"
    $sql_update = "
        UPDATE agendamentos 
        SET status = 'Cancelado' 
        WHERE id_agendamento = ? AND id_cliente = ?
    ";
    
    $stmt_update = $pdo->prepare($sql_update);
    
    if ($stmt_update->execute([$id_agendamento, $id_cliente_logado])) {
        // redirecionar para a página de meus agendamentos com sucesso
        header("Location: meus_agendamentos.php?status=cancelado_sucesso&id_afetado=" . $id_agendamento);
        exit;
    } else {
        $mensagem_erro = "Erro ao cancelar o agendamento: " . $stmt_update->errorInfo()[2];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancelar Agendamento</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Livvic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="cancelar_agendamento.css">
    <link rel="stylesheet" href="../Sidebar/sidebar.css">
</head>
<body>
    <div class="d-flex">
        <?php include '../Sidebar/sidebar.php'; ?>
        <main class="main-content">
            <a href="meus_agendamentos.php" class="back-button">
                <img src="../../imagens/voltar.png" alt="Voltar" width="20" height="20">
                <span class="back-text">Voltar</span>
            </a>
            <h1 class="title-page">Cancelar Agendamento</h1>
            
            <?php if ($mensagem_erro): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $mensagem_erro ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card-cancelamento">
                <!-- informações do agendamento -->
                <div class="info-agrupada">
                    <h5 style="color: #365885; margin-bottom: 20px;">Detalhes do Agendamento</h5>
                    
                    <div class="info-item">
                        <div class="label">Cliente</div>
                        <div class="valor"><?= htmlspecialchars($agendamento['nome_cliente']) ?></div>
                    </div>

                    <div class="info-item">
                        <div class="label">Serviço(s)</div>
                        <div class="valor">
                            <?php foreach ($servicos as $servico): ?>
                                <div><?= htmlspecialchars($servico['nome_servico']) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Data e Hora</div>
                        <div class="valor"><?= $data_formatada ?> às <?= $hora_formatada ?>h</div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Endereço</div>
                        <div class="valor"><?= htmlspecialchars($enderecoFormatado) ?></div>
                        <div class="valor"><?= htmlspecialchars($cidadeEstado) ?></div>
                        <?php if (!empty($referencia)): ?>
                            <div class="valor"><small>Referência: <?= htmlspecialchars($referencia) ?></small></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Valor Total</div>
                        <div class="valor">R$ <?= number_format($valor_total, 2, ',', '.') ?></div>
                    </div>

                    <div class="info-item">
                        <div class="label">Status Atual</div>
                        <div class="valor"><?= htmlspecialchars($agendamento['status']) ?></div>
                    </div>
                </div>

                <!-- aviso de confirmação -->
                <div class="alert alert-warning">
                    <h5>⚠️ Atenção!</h5>
                    <p class="mb-0">Você está prestes a <strong>cancelar</strong> este agendamento. Esta ação é irreversível e liberará o horário para novos agendamentos.</p>
                </div>

                <!-- formulário de cancelamento -->
                <form method="POST" action="" id="formCancelamento">
                    <div class="section text-center">
                        <button type="submit" class="btn-cancelar" id="btnConfirmarCancelamento">
                            <i class="fas fa-times-circle"></i> Confirmar Cancelamento
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // validação do formulário
        document.getElementById('formCancelamento').addEventListener('submit', function(e) {
            return confirm('ATENÇÃO: Tem certeza que deseja CANCELAR este agendamento?\\n\\nCliente: <?= htmlspecialchars($agendamento['nome_cliente']) ?>\\nData: <?= $data_formatada ?> às <?= $hora_formatada ?>h\\n\\nEsta ação não pode ser desfeita!');
        });
    </script>
</body>
</html>