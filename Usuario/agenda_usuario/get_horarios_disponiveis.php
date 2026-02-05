<?php
include '../../db_connect.php';

$data = $_POST['data'] ?? '';
$servicos_str = $_POST['servicos'] ?? '';

// checa conexao com o bd
if (!isset($pdo) || !$pdo) {
    echo json_encode(['error' => 'Falha na conexão com o Banco de Dados.']);
    exit;
}

// valida data simples
if (!$data) {
    echo json_encode(['error' => 'Data não enviada', 'indisponiveis' => []]);
    exit;
}

// verifica se a string de serviços não está vazia após remover espaços
if (strlen(trim($servicos_str)) > 0) {
    $servicos = array_filter(array_map('intval', explode(',', $servicos_str)));
}

// slot (array) horas bases
$slots = [];
for ($h = 7; $h <= 11; $h++) $slots[] = sprintf('%02d:00', $h);
for ($h = 14; $h <= 17; $h++) $slots[] = sprintf('%02d:00', $h);

// puxa agendamentos existentes desse dia
$sql = "SELECT hora_inicio, hora_fim FROM agendamentos WHERE data_agendamento = ? AND status != 'Cancelado'";
$stmt = $pdo->prepare($sql);

if (!$stmt) {
    echo json_encode(['error' => 'Erro ao preparar a consulta de agendamentos.']);
    exit;
}

$stmt->execute([$data]);
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// se houver serviços selecionados, soma a duração total através dos minutos
$duracao_total = 0;
if (count($servicos) > 0) {
    // atualiza para nova tabela
    $placeholders = implode(',', array_fill(0, count($servicos), '?'));

    $duracao_total = count($servicos) * 60; // 1 hora por serviço
}

// para cada slot, verificamos se há colisão
$indisponiveis = [];

foreach ($slots as $slot) {
    // cria o timestamp (transforma uma data e um horário em um único numero) do slot de início usando a data do POST
    $slot_ts_inicio = strtotime($data . ' ' . $slot);
    
    // logica de serviço selecionado
    if ($duracao_total == 0) {
        $colide = false;
        foreach ($agendamentos as $ag) {
            // cria o timestamp de agendamentos existentes usando a data do POST
            $inicio_ts = strtotime($data . ' ' . $ag['hora_inicio']); 
            $fim_ts    = strtotime($data . ' ' . $ag['hora_fim']);
            
            if ($slot_ts_inicio >= $inicio_ts && $slot_ts_inicio < $fim_ts) {
                $colide = true; break;
            }
        }
        if ($colide) $indisponiveis[] = $slot;
    } else {
        // serviços selecionados        
        // calcula fim do novo pedido
        $fim_novo_ts = $slot_ts_inicio + ($duracao_total * 60);
        $colide = false;
        
        foreach ($agendamentos as $ag) {
            // cria o timestamp de agendamentos existentes usando a data do POST
            $inicio_ts = strtotime($data . ' ' . $ag['hora_inicio']); 
            $fim_ts    = strtotime($data . ' ' . $ag['hora_fim']);
            
            // condição de sobreposição
            if ($slot_ts_inicio < $fim_ts && $fim_novo_ts > $inicio_ts) {
                $colide = true; break;
            }
        }
        
        if ($colide) {
            $indisponiveis[] = $slot;
        } else {
             // se existe um agendamento que inicia exatamente no mesmo horário
             foreach ($agendamentos as $ag) {
                // a comparação deve ser feita com a hora bruta
                if (substr($ag['hora_inicio'], 0, 5) === $slot) { 
                    $indisponiveis[] = $slot; 
                    break; 
                }
             }
        }
    }
}

// remove duplicados e ordena
$indisponiveis = array_values(array_unique($indisponiveis));
sort($indisponiveis);

// retorna JSON
echo json_encode(['indisponiveis' => $indisponiveis]);
exit;
?>