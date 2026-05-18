<?php
// trava de segurança
define('IN_APP', true);

// raiz do projeto
define('ROOT_PATH', __DIR__);

// CONFIGURAÇÃO GLOBAL DE ERROS
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// ===== CONFIGURAÇÕES DE SESSÃO =====
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Em produção ou HTTPS local, ativa cookie seguro
if (isset($_SERVER['RENDER']) || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) {
    ini_set('session.cookie_secure', 1);
}

// Inicia a sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carrega configurações
require_once ROOT_PATH . '/codigos_php/config.php';
require_once ROOT_PATH . '/codigos_php/auth.php';
?>
