<?php
// fuso
date_default_timezone_set('America/Sao_Paulo'); 

session_start();

// verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// pega o id do cliente logado
$id_cliente_logado = $_SESSION['user_id']; 

// verificar se está pegando o id correto
error_log("ID do cliente logado: " . $id_cliente_logado);

include '../../db_connect.php'; 

// SQL para puxar os agendamentos do cliente logado, seus dados e os itens de serviço
$sql = "
    SELECT 
        a.id_agendamento, a.data_agendamento, a.hora_inicio, a.status,
        c.nome AS nome_cliente, c.telefone AS telefone_cliente,
        
        /* Endereço Principal (do cliente ou extra) */
        CASE 
            WHEN a.id_endereco = c.id_cliente THEN c.rua 
            ELSE ec.rua 
        END AS rua_agendada,
        CASE 
            WHEN a.id_endereco = c.id_cliente THEN c.numero 
            ELSE ec.numero 
        END AS numero_agendado,
        CASE 
            WHEN a.id_endereco = c.id_cliente THEN c.bairro 
            ELSE ec.bairro 
        END AS bairro_agendado,
        CASE 
            WHEN a.id_endereco = c.id_cliente THEN c.cidade 
            ELSE ec.cidade 
        END AS cidade_agendada,
        CASE 
            WHEN a.id_endereco = c.id_cliente THEN c.estado 
            ELSE ec.estado 
        END AS estado_agendada,
        CASE 
            WHEN a.id_endereco = c.id_cliente THEN c.referencia 
            ELSE ec.referencia 
        END AS referencia_agendada,
        CASE 
            WHEN a.id_endereco = c.id_cliente THEN c.complemento 
            ELSE ec.complemento 
        END AS complemento_agendada,
        
        /* Detalhes do Item de Serviço */
        ia.preco_no_agendamento AS preco_item, 
        sd.nome AS nome_servico
    FROM 
        agendamentos a
    JOIN 
        clientes c ON a.id_cliente = c.id_cliente
    JOIN 
        itens_agendamento ia ON a.id_agendamento = ia.id_agendamento
    JOIN 
        servicos_disponiveis sd ON ia.id_servico = sd.id
    LEFT JOIN 
        enderecos_cliente ec ON a.id_endereco = ec.id_endereco AND a.id_endereco != a.id_cliente 
    WHERE 
        a.id_cliente = ? /* FILTRO CRÍTICO: Somente agendamentos do cliente logado */
    ORDER BY
        a.data_agendamento DESC, a.hora_inicio DESC, a.id_agendamento DESC, ia.id_item ASC 
";

$stmt = $pdo->prepare($sql);
if (!$stmt) {
    die("Erro no prepare: " . $pdo->errorInfo()[2]);
}

if (!$stmt->execute([$id_cliente_logado])) {
    die("Erro no execute: " . $stmt->errorInfo()[2]);
}

$agendamentos_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// processamento dos dados no PHP para consolidar múltiplos serviços por agendamento
$agendamentos = [];
foreach ($agendamentos_raw as $row) {
    $id = $row['id_agendamento'];
    if (!isset($agendamentos[$id])) {
        // inicializa o agendamento
        $agendamentos[$id] = [
            'id_agendamento' => $id,
            'data_agendamento' => $row['data_agendamento'],
            'hora_inicio'    => $row['hora_inicio'],
            'status'         => $row['status'],
            'nome_cliente'   => $row['nome_cliente'],
            'telefone_cliente' => $row['telefone_cliente'],
            'rua_agendada'   => $row['rua_agendada'],
            'numero_agendado'=> $row['numero_agendado'],
            'bairro_agendado'=> $row['bairro_agendado'],
            'cidade_agendada'=> $row['cidade_agendada'],
            'estado_agendada'=> $row['estado_agendada'],
            'referencia_agendada' => $row['referencia_agendada'],
            'complemento_agendada' => $row['complemento_agendada'],
            'valor_total'    => 0,
            'servicos'       => []
        ];
    }
    
    // adiciona o valor do item ao total e armazena os detalhes do serviço
    $agendamentos[$id]['valor_total'] += (float)$row['preco_item'];
    $agendamentos[$id]['servicos'][] = [
        'nome'  => $row['nome_servico']
    ];
}

$agendamentos = array_values($agendamentos);

// função para converter o status do agendamento (ajustada para o cliente)
function getStatusAgendamento($status) {
    switch ($status) {
        case 'Pendente':
            return '<span class="status-pagamento status-pendente">Pendente de Pagamento</span>';
        case 'Confirmado':
            return '<span class="status-pagamento status-confirmado">Agendamento Confirmado</span>';
        case 'Finalizado':
            return '<span class="status-pagamento status-finalizado">Serviço Concluído</span>';
        case 'Cancelado':
             return '<span class="status-pagamento status-cancelado">Agendamento Cancelado</span>';
        default:
            return '<span class="status-pagamento status-desconhecido">Status Desconhecido</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Agendamentos</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Livvic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="../Sidebar/sidebar.css">
    <link rel="stylesheet" href="meus_agendamentos.css"> 
</head>
<body>
    <div class="d-flex">
        <?php include '../Sidebar/sidebar.php'; ?>
        <main class="main-content">         
            
            <?php
                $status = $_GET['status'] ?? '';

                if ($status === 'cancelado_sucesso') {
                    echo '<div class="alert alert-success alert-dismissible fade show" role="alert" style="margin: 20px 0;">';
                    echo 'Agendamento **cancelado** com sucesso! Lamentamos que tenha desmarcado.';
                    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    echo '</div>';
                } elseif ($status === 'reagendado_sucesso') {
                    echo '<div class="alert alert-info alert-dismissible fade show" role="alert" style="margin: 20px 0;">';
                    echo 'Agendamento **reagendado** com sucesso! Verifique os novos detalhes abaixo.';
                    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    echo '</div>';
                } elseif (strpos($status, 'erro') !== false) {
                    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin: 20px 0;">';
                    echo 'Ocorreu um erro ao processar sua solicitação. Tente novamente.';
                    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    echo '</div>';
                }
            ?>

            <h1 class="title-page">Meus Agendamentos</h1>
            
            <h3 class="data-titulo-selecionada">Total de Agendamentos: <?= count($agendamentos) ?></h3>
            
            <div class="agendamentos-list">
                <?php if (count($agendamentos) > 0): ?>
                    <?php foreach ($agendamentos as $agendamento): 
                        // formata o endereço
                        $complementoTexto = trim($agendamento['complemento_agendada'] ?? '');
                        $complementoParte = $complementoTexto !== '' ? ' - ' . $complementoTexto : '';
                        $enderecoFormatado = $agendamento['rua_agendada'] . ', nº ' . $agendamento['numero_agendado'] . $complementoParte . ' – Bairro ' . $agendamento['bairro_agendado'];
                        $cidadeEstado = $agendamento['cidade_agendada'] . ', ' . $agendamento['estado_agendada'];
                        
                        // link do mapa
                        $mapaLink = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($enderecoFormatado . ', ' . $cidadeEstado);
                        
                        // desabilitar botões se o agendamento estiver Cancelado ou Concluído
                        $is_editable = ($agendamento['status'] === 'Pendente' || $agendamento['status'] === 'Confirmado');

                        // informação de data e hora
                        $dataHoraAgendamento = (new DateTime($agendamento['data_agendamento'] . ' ' . $agendamento['hora_inicio']))->format('d/m/Y \à\s H:i');
                    ?>

                    <div class="card-agendamento">
                        <div class="agendamento-topo">
                            <div class="agendamento-cliente">
                                <span class="label">Agendado para:</span>
                                <p class="nome-cliente">
                                    <?= $dataHoraAgendamento ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="agendamento-info">
                            <div class="agendamento-detalhe">
                                <p class="label">Serviço(s) Contratado(s):</p>
                                <?php foreach ($agendamento['servicos'] as $servico): ?>
                                <div class="valor-servico">
                                    <span class="texto-servico-item"><?= htmlspecialchars($servico['nome']) ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="agendamento-endereco">
                            <p class="label">Local do Serviço:</p>
                            <p class="rua"><?= htmlspecialchars($enderecoFormatado) ?></p>
                            <p class="referencia">Ponto de referência: <?= htmlspecialchars($agendamento['referencia_agendada'] ?: 'Não informado.') ?></p>
                            <p class="cidade"><?= htmlspecialchars($cidadeEstado) ?></p>
                            
                            <a href="<?= $mapaLink ?>" target="_blank" class="ver-no-mapa">
                                Ver no mapa
                            </a>
                        </div>

                        <div class="agendamento-valor">
                            <div class="valor-info">
                                <p class="label">Valor Total:</p>
                                <span class="valor-rs">R$ <?= number_format($agendamento['valor_total'], 2, ',', '.') ?></span>
                            </div>
                            <?= getStatusAgendamento($agendamento['status']) ?>
                        </div>

                        <div class="agendamento-botoes">
                            <a 
                                href="<?= $is_editable ? 'reagendar.php?id=' . $agendamento['id_agendamento'] : '#' ?>" 
                                class="btn-acao reagendar <?= $is_editable ? '' : 'disabled' ?>" 
                                <?= $is_editable ? '' : 'onclick="return false;"' ?>
                            >
                                <img src="../../imagens/reagendar.png" alt="Reagendar" class="btn-icon"> Reagendar
                            </a>
                            
                            <a 
                                href="<?= $is_editable ? 'cancelar_agendamento.php?id=' . $agendamento['id_agendamento'] : '#' ?>" 
                                class="btn-acao cancelar <?= $is_editable ? '' : 'disabled' ?>" 
                                <?= $is_editable ? '' : 'onclick="return false;"' ?>
                            >
                                Cancelar
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center mt-5">Você não possui agendamentos ativos ou passados.</p>
                <?php endif; ?>
            </div>
            
        </main>
    </div>
    
    <script>
    // confirmação para cancelamento (redireciona para página de confirmação)
    document.querySelectorAll('.btn-acao.cancelar').forEach(button => {
        button.addEventListener('click', function(e) {
            if (this.classList.contains('disabled')) {
                e.preventDefault();
                return false;
            }
            
            // se for um link válido, pede confirmação antes de redirecionar
            if (this.getAttribute('href') && this.getAttribute('href') !== '#') {
                if (!confirm('ATENÇÃO: Você será redirecionado para a página de confirmação de cancelamento. Deseja continuar?')) {
                    e.preventDefault();
                }
            }
        });
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>