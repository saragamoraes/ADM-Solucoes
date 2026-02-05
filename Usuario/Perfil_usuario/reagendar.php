<?php
// fusio horario correto
date_default_timezone_set('America/Sao_Paulo'); 

session_start();

// verifica se usuario esta logado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// pega o id do cliente da sessão
$id_cliente_logado = $_SESSION['user_id']; 

// verifica se o id do agendamento foi passado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: meus_agendamentos.php?status=erro_id_nao_informado');
    exit;
}

$id_agendamento = intval($_GET['id']);

include '../../db_connect.php';

// busca dados do agendamento atual
$sql_agendamento = "
    SELECT 
        a.id_agendamento, a.data_agendamento, a.hora_inicio, a.status,
        a.id_cliente, a.id_endereco,
        c.nome AS nome_cliente, c.telefone,
        c.rua, c.numero, c.bairro, c.cidade, c.estado, c.complemento, c.referencia,
        s.id AS id_servico, s.nome AS nome_servico,
        ia.preco_no_agendamento
    FROM 
        agendamentos a
    JOIN 
        clientes c ON a.id_cliente = c.id_cliente
    JOIN 
        itens_agendamento ia ON a.id_agendamento = ia.id_agendamento
    JOIN 
        servicos_disponiveis s ON ia.id_servico = s.id
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

$agendamento_atual = $stmt->fetch(PDO::FETCH_ASSOC);

// verifica se o agendamento existe e pertence ao cliente
if (!$agendamento_atual) {
    header('Location: meus_agendamentos.php?status=erro_agendamento_nao_encontrado');
    exit;
}

// verifica se o agendamento pode ser reagendado
if (!in_array($agendamento_atual['status'], ['Pendente', 'Confirmado'])) {
    header('Location: meus_agendamentos.php?status=erro_agendamento_nao_reagendavel');
    exit;
}

// gera dias disponiveis
$hoje = new DateTime();
$dias = [];
for ($i = 1; $i <= 7; $i++) {
    $dia = clone $hoje;
    $dia->modify("+$i day");
    $dias[] = $dia;
}

// horários disponíveis pré definidos (os horários que o Adamastor trabalha)
$horarios_disponiveis = ['08:00', '09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00'];

// processa agendamento POST
$mensagem_erro = '';
$mensagem_sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_data = $_POST['nova_data'] ?? '';
    $novo_horario = $_POST['novo_horario'] ?? '';
    
    // validações
    if (empty($nova_data) || empty($novo_horario)) {
        $mensagem_erro = "Por favor, selecione uma data e horário para o reagendamento.";
    } else {
        // verifica se a nova data não é no passado
        $data_hoje = new DateTime();
        $data_agendamento = DateTime::createFromFormat('Y-m-d', $nova_data);
        
        if ($data_agendamento < $data_hoje->setTime(0, 0, 0)) {
            $mensagem_erro = "Não é possível agendar para datas passadas.";
        } else {
            // verificar disponibilidade do horário
            $sql_verifica = "
                SELECT id_agendamento 
                FROM agendamentos 
                WHERE data_agendamento = ? 
                AND hora_inicio = ? 
                AND status NOT IN ('Cancelado', 'Finalizado')
                AND id_agendamento != ?
            ";
            
            $stmt_verifica = $pdo->prepare($sql_verifica);
            
            if (!$stmt_verifica->execute([$nova_data, $novo_horario, $id_agendamento])) {
                $mensagem_erro = "Erro ao verificar disponibilidade.";
            } else {
                $result_verifica = $stmt_verifica->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($result_verifica) > 0) {
                    $mensagem_erro = "Este horário já está ocupado. Por favor, escolha outro horário.";
                } else {
                    // calcular hora_fim (assumindo 1 hora de duração padrão)
                    $hora_inicio_obj = DateTime::createFromFormat('H:i', $novo_horario);
                    $hora_fim_obj = clone $hora_inicio_obj;
                    $hora_fim_obj->modify("+60 minutes");
                    $nova_hora_fim = $hora_fim_obj->format('H:i:s');
                    
                    // atualizar o agendamento
                    $sql_update = "
                        UPDATE agendamentos 
                        SET data_agendamento = ?, hora_inicio = ?, hora_fim = ? 
                        WHERE id_agendamento = ? AND id_cliente = ?
                    ";
                    
                    $stmt_update = $pdo->prepare($sql_update);
                    
                    if ($stmt_update->execute([$nova_data, $novo_horario, $nova_hora_fim, $id_agendamento, $id_cliente_logado])) {
                        $mensagem_sucesso = "Agendamento reagendado com sucesso!";
                        // redirecionar após 2 segundos
                        header("Refresh: 2; URL=meus_agendamentos.php?status=reagendado_sucesso");
                    } else {
                        $mensagem_erro = "Erro ao atualizar o agendamento: " . $stmt_update->errorInfo()[2];
                    }
                }
            }
        }
    }
}

// formata dados para exibição
$data_atual_formatada = (new DateTime($agendamento_atual['data_agendamento']))->format('d/m/Y');
$hora_atual_formatada = (new DateTime($agendamento_atual['hora_inicio']))->format('H:i');

// formata endereço
$complementoTexto = trim($agendamento_atual['complemento'] ?? '');
$complementoParte = $complementoTexto !== '' ? ' - ' . $complementoTexto : '';
$enderecoFormatado = $agendamento_atual['rua'] . ', nº ' . $agendamento_atual['numero'] . $complementoParte . ' – Bairro ' . $agendamento_atual['bairro'];
$cidadeEstado = $agendamento_atual['cidade'] . ', ' . $agendamento_atual['estado'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reagendar Agendamento</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Livvic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="reagendar.css">
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
            <h1 class="title-page">Reagendar Serviço</h1>
            
            <?php if ($mensagem_sucesso): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $mensagem_sucesso ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($mensagem_erro): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $mensagem_erro ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card-reagendamento">
                <!-- informações atuais do agendamento -->
                <div class="info-agrupada">
                    <h5 style="color: #365885; margin-bottom: 20px;">Agendamento Atual</h5>
                    
                    <div class="info-item">
                        <div class="label">Serviço</div>
                        <div class="valor"><?= htmlspecialchars($agendamento_atual['nome_servico']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Data e Hora Atuais</div>
                        <div class="valor"><?= $data_atual_formatada ?> às <?= $hora_atual_formatada ?>h</div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Endereço</div>
                        <div class="valor"><?= htmlspecialchars($enderecoFormatado) ?></div>
                        <div class="valor"><?= htmlspecialchars($cidadeEstado) ?></div>
                        <?php if (!empty($agendamento_atual['referencia'])): ?>
                            <div class="valor"><small>Referência: <?= htmlspecialchars($agendamento_atual['referencia']) ?></small></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Valor</div>
                        <div class="valor">R$ <?= number_format($agendamento_atual['preco_no_agendamento'], 2, ',', '.') ?></div>
                    </div>
                </div>

                <!-- formulário de reagendamento -->
                <form method="POST" action="" id="formReagendamento">
                    <!-- seleção de data -->
                    <div class="section">
                        <input type="hidden" name="nova_data" id="nova_data" value="">
                        <h2>Selecione a nova data:</h2>
                        <div class="calendar">
                            <?php foreach ($dias as $index => $dia): 
                                $numero = $dia->format("d");
                                $semana = $dia->format("D");
                                $semana_pt = [
                                    "Sun" => "D","Mon" => "S","Tue" => "T",
                                    "Wed" => "Q","Thu" => "Q","Fri" => "S","Sat" => "S"
                                ][$semana];
                                
                                $data_completa = $dia->format("Y-m-d");
                            ?>
                                <div class="day" data-date="<?= $data_completa ?>">
                                    <span class="weekday"><?= $semana_pt ?></span>
                                    <span class="number"><?= $numero ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- seleção de horário -->
                    <div class="section">
                        <input type="hidden" name="novo_horario" id="novo_horario" value="">
                        <h2>Selecione o novo horário:</h2>
                        <div class="horarios" id="listaHorarios">
                            <?php foreach ($horarios_disponiveis as $horario): ?>
                                <button type="button" class="horario-btn" data-horario="<?= $horario ?>">
                                    <?= $horario ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="section text-center">
                        <button type="submit" class="btn-reagendar" id="btnConfirmar" disabled>Reagendar</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // seleção de data
        document.querySelectorAll('.calendar .day').forEach(day => {
            day.addEventListener('click', function() {
                // remove seleção anterior
                document.querySelectorAll('.calendar .day').forEach(d => {
                    d.classList.remove('selected');
                });
                
                // adiciona seleção atual
                this.classList.add('selected');
                
                // atualiza o campo hidden
                const dataSelecionada = this.getAttribute('data-date');
                document.getElementById('nova_data').value = dataSelecionada;
                
                // habilita o botão de confirmação se ambos estiverem selecionados
                verificarSelecaoCompleta();
            });
        });

        // seleção de horário
        document.querySelectorAll('.horario-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // remove seleção anterior
                document.querySelectorAll('.horario-btn').forEach(b => {
                    b.classList.remove('active');
                });
                
                // adiciona seleção atual
                this.classList.add('active');
                
                // atualiza o campo hidden
                const horarioSelecionado = this.getAttribute('data-horario');
                document.getElementById('novo_horario').value = horarioSelecionado;
                
                // habilita o botão de confirmação se ambos estiverem selecionados
                verificarSelecaoCompleta();
            });
        });

        // função para verificar se data e horário estão selecionados
        function verificarSelecaoCompleta() {
            const dataSelecionada = document.getElementById('nova_data').value;
            const horarioSelecionado = document.getElementById('novo_horario').value;
            const btnConfirmar = document.getElementById('btnConfirmar');
            
            if (dataSelecionada && horarioSelecionado) {
                btnConfirmar.disabled = false;
            } else {
                btnConfirmar.disabled = true;
            }
        }

        // validação do formulário
        document.getElementById('formReagendamento').addEventListener('submit', function(e) {
            const data = document.getElementById('nova_data').value;
            const horario = document.getElementById('novo_horario').value;
            
            if (!data || !horario) {
                e.preventDefault();
                alert('Por favor, selecione uma data e horário para o reagendamento.');
                return false;
            }
            
            // verifica se a data não é no passado
            const dataSelecionada = new Date(data);
            const hoje = new Date();
            hoje.setHours(0, 0, 0, 0);
            
            if (dataSelecionada < hoje) {
                e.preventDefault();
                alert('Não é possível agendar para datas passadas.');
                return false;
            }
            
            return confirm('Tem certeza que deseja reagendar este serviço para ' + 
                         new Date(data).toLocaleDateString('pt-BR') + ' às ' + horario + '?');
        });

        // inicia com botão desabilitado
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('btnConfirmar').disabled = true;
        });
    </script>
</body>
</html>