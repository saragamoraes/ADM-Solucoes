<?php
// fuso
date_default_timezone_set('America/Sao_Paulo'); 

include '../../db_connect.php';

// define a data selecionada: pega da URL (GET) ou usa a data de hoje
$dataSelecionada = isset($_GET['data']) ? $_GET['data'] : (new DateTime())->format("Y-m-d");

// gerar os próximos 7 dias para o calendário
$hoje = new DateTime();
$dias = [];

for ($i = 0; $i < 7; $i++) {
    $dia = clone $hoje;
    $dia->modify("+$i day");
    $dias[] = $dia;
}

// SQL para puxar os agendamentos do dia, seus dados de cliente e os itens de serviço.
$sql = "
    SELECT 
        a.id_agendamento, a.hora_inicio, a.status,
        c.nome AS nome_cliente, c.telefone AS telefone_cliente,
        
        /* Endereço Principal (do cliente ou extra) */
        CASE WHEN a.id_endereco = c.id_cliente THEN c.rua ELSE ec.rua END AS rua_agendada,
        CASE WHEN a.id_endereco = c.id_cliente THEN c.numero ELSE ec.numero END AS numero_agendado,
        CASE WHEN a.id_endereco = c.id_cliente THEN c.bairro ELSE ec.bairro END AS bairro_agendado,
        CASE WHEN a.id_endereco = c.id_cliente THEN c.cidade ELSE ec.cidade END AS cidade_agendada,
        CASE WHEN a.id_endereco = c.id_cliente THEN c.estado ELSE ec.estado END AS estado_agendada,
        CASE WHEN a.id_endereco = c.id_cliente THEN c.referencia ELSE ec.referencia END AS referencia_agendada,
        /* complemento (se existir) */
        CASE WHEN a.id_endereco = c.id_cliente THEN c.complemento ELSE ec.complemento END AS complemento_agendada,
        
        /* Detalhes do Item de Serviço - ATUALIZADO para nova tabela */
        ia.preco_no_agendamento AS preco_item, 
        sd.nome AS nome_servico  -- 🚨 MUDANÇA: s → sd (servicos_disponiveis)
    FROM 
        agendamentos a
    JOIN 
        clientes c ON a.id_cliente = c.id_cliente
    JOIN 
        itens_agendamento ia ON a.id_agendamento = ia.id_agendamento
    JOIN 
        servicos_disponiveis sd ON ia.id_servico = sd.id  -- 🚨 MUDANÇA CRÍTICA
    LEFT JOIN 
        enderecos_cliente ec ON a.id_endereco = ec.id_endereco AND a.id_endereco != a.id_cliente 
    WHERE 
        a.data_agendamento = ? 
    ORDER BY
        a.hora_inicio ASC, a.id_agendamento ASC, ia.id_item ASC 
";

$stmt = $pdo->prepare($sql);
if (!$stmt) {
    die("Erro no prepare: " . $pdo->errorInfo()[2]);
}

if (!$stmt->execute([$dataSelecionada])) {
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

// reindexa o array para a exibição no HTML
$agendamentos = array_values($agendamentos);

// função para converter o status do agendamento
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

$base_path_sidebar = '../'; 
$base_path_images = '../../imagens/'; 
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADM | Visualizar Agenda</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Livvic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="<?= $base_path_sidebar ?>Sidebar/sidebar.css">
    
    <link rel="stylesheet" href="agenda_visualizar.css">
</head>
<body>
    <div class="d-flex">
         <?php 
        // inclui o sidebar passando as variáveis de caminho
        $baseSidebar = '../Sidebar/';
        $baseImages = '../../imagens/';
        include $base_path_sidebar . 'Sidebar/sidebar.php'; 
        ?>
        
        <main class="main-content">         

            <?php
                // bloco de notificação
                $status = $_GET['status'] ?? '';
                $id_afetado = $_GET['id_afetado'] ?? '';

                if ($status === 'cancelado_sucesso') {
                    echo '<div class="alert alert-success alert-dismissible fade show" role="alert" style="margin: 20px 0;">';
                    echo '✅ Agendamento **#' . htmlspecialchars($id_afetado) . '** cancelado com sucesso!';
                    
                    // botão de notificação wpp
                    if (isset($_GET['whatsapp_link'])) {
                        echo '<div class="mt-2">';
                        echo '<a href="' . urldecode($_GET['whatsapp_link']) . '" target="_blank" class="btn btn-success btn-sm">';
                        echo '<i class="fab fa-whatsapp"></i> Notificar Cliente no WhatsApp';
                        echo '</a>';
                        echo '</div>';
                    }
                    
                    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    echo '</div>';
                } elseif ($status === 'reagendado_sucesso') {
                    echo '<div class="alert alert-info alert-dismissible fade show" role="alert" style="margin: 20px 0;">';
                    echo '🔄 Agendamento **#' . htmlspecialchars($id_afetado) . '** reagendado com sucesso!';
                    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    echo '</div>';
                } elseif ($status === 'cancelado_pelo_cliente') {
                    echo '<div class="alert alert-warning alert-dismissible fade show" role="alert" style="margin: 20px 0;">';
                    echo 'ALERTA: O Agendamento **#' . htmlspecialchars($id_afetado) . '** foi CANCELADO pelo cliente.';
                    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    echo '</div>';
                } elseif ($status === 'reagendado_pelo_cliente') {
                    echo '<div class="alert alert-info alert-dismissible fade show" role="alert" style="margin: 20px 0;">';
                    echo 'INFO: O Agendamento **#' . htmlspecialchars($id_afetado) . '** foi REAGENDADO pelo cliente. Verifique os novos detalhes.';
                    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    echo '</div>';
                }
            ?>

    <h1 class="title-page">Visualizar Agenda</h1>

    <div class="agenda-container">
            <div class="section">
                <h2>Selecione a data:</h2>
                <div class="calendar">
                    <?php 
                    // mapeamento dos dias da semana
                    $semana_map = [
                        "Sun" => "D", "Mon" => "S", "Tue" => "T", "Wed" => "Q", 
                        "Thu" => "Q", "Fri" => "S", "Sat" => "S"
                    ];
                    
                    foreach ($dias as $index => $dia):
                        $date_format_db = $dia->format("Y-m-d");
                        $numero = $dia->format("d");
                        $semana = $dia->format("D");
                        $semana_pt = $semana_map[$semana];

                        $classes = "day";
                        if ($date_format_db === (new DateTime())->format("Y-m-d")) {
                            $classes .= " today";
                        }
                        if ($date_format_db === $dataSelecionada) {
                            $classes .= " selected";
                        }
                    ?>
                        <a href="?data=<?= $date_format_db ?>" class="<?= $classes ?>" data-date="<?= $date_format_db ?>">
                            <span class="weekday">
                                <?= $date_format_db === (new DateTime())->format("Y-m-d") ? "HOJE" : $semana_pt ?>
                            </span>
                            <span class="number"><?= $numero ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <h3 class="data-titulo-selecionada">Agendamentos para: <?= (new DateTime($dataSelecionada))->format('d/m/Y') ?> (<?= count($agendamentos) ?>)</h3>
            
            <div class="agendamentos-list">
                <?php if (count($agendamentos) > 0): ?>
                    <?php foreach ($agendamentos as $agendamento): 
                        $nomeCompleto = $agendamento['nome_cliente'];
                        
                        // formata o endereço (inclui complemento se houver)
                        $complementoTexto = trim($agendamento['complemento_agendada'] ?? '');
                        $complementoParte = $complementoTexto !== '' ? ' - ' . $complementoTexto : '';
                        $enderecoFormatado = $agendamento['rua_agendada'] . ', nº ' . $agendamento['numero_agendado'] . $complementoParte . ' – Bairro ' . $agendamento['bairro_agendado'];
                        $cidadeEstado = $agendamento['cidade_agendada'] . ', ' . $agendamento['estado_agendada'];
                        
                        // link do mapa
                        $mapaLink = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($enderecoFormatado . ', ' . $cidadeEstado);

                        // link do wpp
                        $telefoneLimpo = preg_replace('/\D/', '', $agendamento['telefone_cliente']);
                        $whatsappLink = "https://wa.me/55{$telefoneLimpo}";
                    ?>

                    <div class="card-agendamento">
                        <div class="agendamento-topo">
                            <div class="agendamento-cliente">
                                <span class="label">Cliente:</span>
                                <p class="nome-cliente">
                                    <?= htmlspecialchars($nomeCompleto) ?>
                                </p>
                            </div>
                            <div class="agendamento-hora">
                                <?= (new DateTime($agendamento['hora_inicio']))->format('H:i') ?>
                            </div>
                        </div>
                        
                        <div class="agendamento-info">
                            <div class="agendamento-detalhe">
                                <p class="label">Serviço(s):</p>
                                <?php foreach ($agendamento['servicos'] as $servico): ?>
                                <div class="valor-servico">
                                    <span class="texto-servico-item"><?= htmlspecialchars($servico['nome']) ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="agendamento-endereco">
                            <p class="label">Endereço:</p>
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

                        <div class="agendamento-contato">
                            <!-- botão wpp -->
                             <p class="label">Conversar com Cliente: </p>
                            <a href="https://wa.me/55<?= preg_replace('/\D/', '', $agendamento['telefone_cliente']) ?>?text=Olá <?= urlencode($agendamento['nome_cliente']) ?>, tudo bem? Gostaria de falar sobre seu agendamento de <?= htmlspecialchars($agendamento['servicos'][0]['nome']) ?> agendado para <?= (new DateTime($agendamento['hora_inicio']))->format('d/m/Y \à\s H:i') ?>." 
                            target="_blank" class="btn-whatsapp">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                        </div>

                        <!-- botões do agendamento -->
                        <div class="agendamento-botoes">
                            <?php
                                // desabilitar botões se o agendamento estiver cancelado ou finalizado
                                $is_editable = ($agendamento['status'] === 'Pendente' || $agendamento['status'] === 'Confirmado');
                            ?>
                            
                            <a 
                                href="<?= $is_editable ? 'reagendar_adm.php?id=' . $agendamento['id_agendamento'] : '#' ?>" 
                                class="btn-acao reagendar <?= $is_editable ? '' : 'disabled' ?>" 
                                <?= $is_editable ? '' : 'onclick="return false;"' ?>
                            >
                                <img src="../../imagens/reagendar.png" alt="Reagendar" class="btn-icon"> Reagendar
                            </a>
                            
                            <a 
                                href="<?= $is_editable ? 'cancelar_agendamento_adm.php?id=' . $agendamento['id_agendamento'] : '#' ?>" 
                                class="btn-acao cancelar <?= $is_editable ? '' : 'disabled' ?>" 
                                <?= $is_editable ? '' : 'onclick="return false;"' ?>
                            >
                                Desmarcar
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center mt-5">Não há agendamentos para a data selecionada (<?= (new DateTime($dataSelecionada))->format('d/m/Y') ?>).</p>
                <?php endif; ?>
            </div>
            
        </main>
    </div>

    <script>
        // seleção do dia na barra do calendário
        document.querySelectorAll(".calendar .day").forEach(day => {
            day.addEventListener("click", (e) => {
                document.querySelectorAll(".calendar .day").forEach(d => d.classList.remove("selected"));
                day.classList.add("selected");
            });
        });
    </script>

    <script>
        // confirmação para cancelamento direto
        document.querySelectorAll('.btn-acao.cancelar').forEach(button => {
            button.addEventListener('click', function(e) {
                if (this.getAttribute('href')) {
                    if (!confirm('ATENÇÃO: Você está prestes a CANCELAR este agendamento. Tem certeza que deseja desmarcar? Esta ação pode ser irreversível.')) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>