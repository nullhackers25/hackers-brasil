<?php
// redirecionar.php - Endpoint seguro para redirecionamentos internos

// ===== 1. INICIALIZAÇÃO =====
require_once $_SERVER['DOCUMENT_ROOT'] . '/Hackers_Brasil_New/init.php';

// ===== 2. VERIFICA AUTENTICAÇÃO =====
require_once ROOT_PATH . '/codigos_php/auth.php'; // já faz o redirecionamento se não estiver logado

// ===== 3. RECEBE O DESTINO =====
$destino = $_GET['destino'] ?? '';

// Lista de destinos permitidos (whitelist) - SEGURANÇA!
$destinos_permitidos = [
    'Sistemas-Operacionais' => '/Hackers_Brasil/public/painel/Sistemas_Operacionais/Sistemas_Operacionais.php',  
    'sistemas-operacionais' => '/Hackers_Brasil/public/painel/Sistemas_Operacionais/Sistemas_Operacionais.php',   
    'Linux' => '/Hackers_Brasil/public/painel/Sistemas_Operacionais/Linux/Linux.php',
    'linux' => '/Hackers_Brasil/public/painel/Sistemas_Operacionais/Linux/Linux.php',
    'Kali-Linux' => '/Hackers_Brasil/public/painel/Sistemas_Operacionais/Linux/Kali_Linux/Kali_Linux.php',
    'kali-linux' => '/Hackers_Brasil/public/painel/Sistemas_Operacionais/Linux/Kali_Linux/Kali_Linux.php',
    'Gerenciamento-Pacotes' => '/Hackers_Brasil/public/painel/Sistemas_Operacionais/Linux/Kali_Linux/Gerenciamento_Pacotes.php',
    'gerenciamento-pacotes' => '/Hackers_Brasil/public/painel/Sistemas_Operacionais/Linux/Kali_Linux/Gerenciamento_Pacotes.php',
    'Gerenciamento-Usuarios' => '/Hackers_Brasil/public/painel/Sistemas_Operacionais/Linux/Kali_Linux/Gerenciamento_Usuarios.php',
    'gerenciamento-usuarios' => '/Hackers_Brasil/public/painel/Sistemas_Operacionais/Linux/Kali_Linux/Gerenciamento_Usuarios.php',
    // adicione mais destinos conforme necessário
];

// ===== 4. VALIDA O DESTINO =====
if (isset($destinos_permitidos[$destino])) {
    header("Location: " . $destinos_permitidos[$destino]);
    exit;
} else {
    // Destino inválido ou não autorizado
    header("HTTP/1.0 404 Not Found");
    echo "Página não encontrada.";
    exit;
}
?>
