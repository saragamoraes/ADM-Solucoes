<?php
session_start();

// verifica se usuário esta logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// variável para armazenar mensagens de erro/status
$error_message = null;

// pega o id do cliente da sessão
$clienteLogadoId = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    header('Content-Type: text/html; charset=utf-8');

    // recebe dados
    $dataSelecionada = isset($_POST['data_selecionada']) ? $_POST['data_selecionada'] : null;
    $horarioSelecionado = isset($_POST['horario_selecionado']) ? $_POST['horario_selecionado'] : null;
    $servicosIdsString = isset($_POST['servicos_ids_selecionados']) ? $_POST['servicos_ids_selecionados'] : '';
   
    $enderecoIdValor = isset($_POST['endereco_id_selecionado']) ? $_POST['endereco_id_selecionado'] : '';
   
    $enderecoId = 0;
   
    if (strpos($enderecoIdValor, 'principal_') === 0) {
        $enderecoId = $clienteLogadoId; 
    } elseif (is_numeric($enderecoIdValor)) {
        $enderecoId = (int)$enderecoIdValor;
    }

    if (empty($dataSelecionada) || empty($horarioSelecionado) || empty($servicosIdsString) || $enderecoId === 0) {
        $error_message = "Erro de validação: Data, Hora, Serviço ou Endereço ($enderecoIdValor) estão faltando.";
    }

    if (is_null($error_message)) {

        $servicosIds = explode(',', $servicosIdsString);
        $servicos_para_agendar = [];

        $pdo->beginTransaction();

        try {
            // busca informações do serviço
            $servicos_ids_placeholder = implode(',', array_fill(0, count($servicosIds), '?'));
           
            $stmt_duracao = $pdo->prepare("
                SELECT id, valor
                FROM servicos_disponiveis
                WHERE id IN ($servicos_ids_placeholder)
            ");
           
            if (!$stmt_duracao) {
                throw new Exception("Erro PREPARE (Serviços): " . $pdo->errorInfo()[2]);
            }
           
            $stmt_duracao->execute($servicosIds);
            $resultado_duracao = $stmt_duracao->fetchAll(PDO::FETCH_ASSOC);
           
            foreach ($resultado_duracao as $row) {
                $servicos_para_agendar[] = $row;
            }

            if (empty($servicos_para_agendar)) {
                throw new Exception("Nenhum serviço válido encontrado.");
            }

            $sql_conflito = "
                SELECT id_agendamento
                FROM agendamentos
                WHERE data_agendamento = ?
                AND hora_inicio = ?
                AND status != 'Cancelado'
            ";

            $stmt_conflito = $pdo->prepare($sql_conflito);
            if (!$stmt_conflito) {
                throw new Exception("Erro PREPARE (Conflito): " . $pdo->errorInfo()[2]);
            }

            $stmt_conflito->execute([$dataSelecionada, $horarioSelecionado]);
            $resultado_conflito = $stmt_conflito->fetchAll(PDO::FETCH_ASSOC);
           
            if (count($resultado_conflito) > 0) {
                throw new Exception("O horário de $horarioSelecionado já está reservado. Por favor, escolha outro horário.");
            }
           
            // bloqueia horários q ja estao agendados
            $status = 'Pendente';
            $horaFim = $horarioSelecionado; 

            $stmt_agenda = $pdo->prepare("
                INSERT INTO agendamentos (id_cliente, id_endereco, data_agendamento, hora_inicio, hora_fim, status, data_criacao)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            if (!$stmt_agenda) {
                throw new Exception("Erro PREPARE (Agendamento): " . $pdo->errorInfo()[2]);
            }
           
            if (!$stmt_agenda->execute([$clienteLogadoId, $enderecoId, $dataSelecionada, $horarioSelecionado, $horaFim, $status])) {
                throw new Exception("Erro EXECUTE (Agendamento): " . $stmt_agenda->errorInfo()[2]);
            }
           
            $idAgendamento = $pdo->lastInsertId();
           
            // insere itens do agendamento
            if ($idAgendamento) {
                foreach ($servicos_para_agendar as $servico) {
                    $stmt_item = $pdo->prepare("
                        INSERT INTO itens_agendamento (id_agendamento, id_servico, preco_no_agendamento)
                        VALUES (?, ?, ?)
                    ");
                    if (!$stmt_item) {
                        throw new Exception("Erro PREPARE (Item): " . $pdo->errorInfo()[2]);
                    }
                   
                    if (!$stmt_item->execute([$idAgendamento, $servico['id'], $servico['valor']])) {
                        throw new Exception("Erro EXECUTE (Item): " . $stmt_item->errorInfo()[2]);
                    }
                }
            } else {
                throw new Exception("ID do Agendamento não gerado.");
            }

            $pdo->commit();
            
            header("Location: Usuario/agenda_usuario/agenda.php?status=success");
            exit();

        } catch (Exception $e) {
            $pdo->rollback();
            $error_message = "Falha no Agendamento: " . $e->getMessage();
        }
    }
}

// mesagem de erro
if (!is_null($error_message)) {
    echo "<h1>ERRO CRÍTICO NO AGENDAMENTO</h1>";
    echo "<p>$error_message</p>";
    echo "<p>Volte para a tela anterior e tente novamente.</p>";
}
?>