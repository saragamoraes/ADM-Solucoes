<?php
session_start();

// fuso
date_default_timezone_set('America/Sao_Paulo');

// verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include __DIR__ . '../../db_connect.php'; 

$id_cliente_logado = $_SESSION['user_id'];

// dados recebidos via POST (do formulário reagendar.php)
$id_agendamento = $_POST['id_agendamento'] ?? null;
$nova_data = $_POST['nova_data'] ?? null;
$novo_horario = $_POST['novo_horario'] ?? null;

if (!$id_agendamento || !$nova_data || !$novo_horario) {
    header("Location: meus_agendamentos.php?status=erro_parametros_reagendar");
    exit();
}

// garante que o horário esteja no formato h:i:s para o banco
$novo_horario_db = $novo_horario . ':00';

// verifica se o agendamento pertence ao cliente
$sqlCheck = "SELECT id_agendamento FROM agendamentos WHERE id_agendamento = ? AND id_cliente = ?";
$stmt = $pdo->prepare($sqlCheck);

if (!$stmt->execute([$id_agendamento, $id_cliente_logado])) {
    header("Location: meus_agendamentos.php?status=erro_reagendar_db");
    exit();
}

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($result) === 0) {
    // nenhum agendamento encontrado para este cliente com este id
    header("Location: meus_agendamentos.php?status=nao_autorizado_reagendar");
    exit();
}

// atualiza a data e horário no banco
$sqlUpdate = "UPDATE agendamentos SET data_agendamento = ?, hora_inicio = ?, status = 'Confirmado' WHERE id_agendamento = ?";
$stmt = $pdo->prepare($sqlUpdate);

if ($stmt->execute([$nova_data, $novo_horario_db, $id_agendamento])) {
    // redireciona para a página do cliente com status de sucesso
    header("Location: meus_agendamentos.php?status=reagendado_sucesso");
    exit();
} else {
    header("Location: meus_agendamentos.php?status=erro_reagendar_db");
    exit();
}
?>