<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    exit;  // Não faz nada se o usuário não estiver logado
}

require 'conexao.php';

$uid = $_SESSION['usuario_id'];

// Remove usuários inativos (sem atividade por 30 minutos)
$stmt = $conn->prepare("
    DELETE FROM usuarios_online
    WHERE last_activity < NOW() - INTERVAL '30 minutes'
");
$stmt->execute();

// Verificação de log (deve aparecer no log de erros)
error_log('Usuários inativos removidos.');

// Atualiza a atividade do usuário
$stmt = $conn->prepare("
    UPDATE usuarios_online
    SET last_activity = NOW()
    WHERE user_id = :uid
");
$stmt->execute(['uid' => $uid]);
?>
