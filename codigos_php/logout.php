<?php
session_start();

// REMOVE DO BANCO ANTES DE DESTRUIR SESSÃO
if (isset($_SESSION['usuario_id'])) {
    $userId = $_SESSION['usuario_id'];
    
    // AJUSTE O CAMINHO CONFORME SUA ESTRUTURA
    require_once $_SERVER['DOCUMENT_ROOT'] . '/Hackers_Brasil_New/codigos_php/conexao.php';
    
    // REMOVE DA TABELA ONLINE
    $stmt = $conn->prepare("DELETE FROM usuarios_online WHERE user_id = :uid");
    $stmt->execute(['uid' => $userId]);
}

// DESTRÓI SESSÃO
session_unset();
session_destroy();

// REDIRECIONA PARA LOGIN (CAMINHO ABSOLUTO)
header("Location: /Hackers_Brasil_New/public/login.php");
exit;
