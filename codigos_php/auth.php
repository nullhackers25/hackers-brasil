<?php
// auth.php - Sistema de autenticação centralizado com sessão única

if (!defined('IN_APP') || IN_APP !== true) {
    http_response_code(403);
    exit('Acesso negado.');
}

// Inicia sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica login básico
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: /Hackers_Brasil_New/public/login.php");
    exit;
}

// Conexão com banco
require_once ROOT_PATH . '/codigos_php/conexao.php';

// ===== SISTEMA DE SESSÃO ÚNICA =====
// Verifica se esta sessão é a ÚLTIMA válida
try {
    $stmt = $conn->prepare("
        SELECT session_id FROM usuarios_online 
        WHERE user_id = :uid 
        ORDER BY login_time DESC 
        LIMIT 1
    ");
    $stmt->execute(['uid' => $_SESSION['usuario_id']]);
    $ultima_sessao = $stmt->fetchColumn();
    
    // Se não existir registro, força logout
    if (!$ultima_sessao) {
        error_log("Sessão sem registro - Usuário: " . $_SESSION['usuario_id']);
        session_unset();
        session_destroy();
        header("Location: /Hackers_Brasil_New/public/login.php?forced=1");
        exit;
    }
    
    // Se o ID da sessão atual for diferente do último registrado
    if ($ultima_sessao !== session_id()) {
        error_log("Sessão antiga detectada e destruída - Usuário: " . $_SESSION['usuario_id']);
        session_unset();
        session_destroy();
        header("Location: /Hackers_Brasil_New/public/login.php?forced=1");
        exit;
    }
    
} catch (PDOException $e) {
    error_log("Erro na verificação de sessão única: " . $e->getMessage());
    session_unset();
    session_destroy();
    header("Location: /Hackers_Brasil_New/public/login.php?error=db");
    exit;
}

// ===== VERIFICAÇÃO DE INATIVIDADE =====
$timeout = 28800; // 8 horas

if (isset($_SESSION['ultima_atividade'])) {
    $inativo = time() - $_SESSION['ultima_atividade'];
    
    if ($inativo > $timeout) {
        // Remove da tabela antes de destruir
        try {
            $stmt = $conn->prepare("DELETE FROM usuarios_online WHERE user_id = :uid");
            $stmt->execute(['uid' => $_SESSION['usuario_id']]);
        } catch (Exception $e) {
            error_log("Erro ao remover por timeout: " . $e->getMessage());
        }
        
        session_unset();
        session_destroy();
        header("Location: /Hackers_Brasil_New/public/login.php?timeout=1");
        exit;
    }
}

// ===== ATUALIZA TIMESTAMP =====
$_SESSION['ultima_atividade'] = time();

// ===== ATUALIZA TABELA ONLINE =====
try {
    $updateStmt = $conn->prepare("
        UPDATE usuarios_online
        SET last_activity = NOW()
        WHERE user_id = :uid
    ");
    $updateStmt->execute(['uid' => $_SESSION['usuario_id']]);
    
} catch (PDOException $e) {
    error_log("Erro ao atualizar atividade: " . $e->getMessage());
}

// ===== LIMPEZA DE INATIVOS =====
try {
    $conn->exec("
        DELETE FROM usuarios_online 
        WHERE last_activity < NOW() - INTERVAL '8 hours'
    ");
} catch (PDOException $e) {
    error_log("Erro na limpeza: " . $e->getMessage());
}
?>
