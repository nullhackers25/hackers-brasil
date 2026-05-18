<?php
// Ativar erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'conexao.php';
require_once 'config.php';

$clientId = GOOGLE_CLIENT_ID;
$clientSecret = GOOGLE_CLIENT_SECRET;
$redirectUri = GOOGLE_REDIRECT_URI;

if (!isset($_GET['code'])) {
    die('Código não recebido');
}

$code = $_GET['code'];

// 1. Obter token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$tokenData = [
    'code' => $code,
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri' => $redirectUri,
    'grant_type' => 'authorization_code'
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

$tokenResponse = curl_exec($ch);
curl_close($ch);

$tokenInfo = json_decode($tokenResponse, true);

if (!isset($tokenInfo['access_token'])) {
    die('Erro ao obter token');
}

// 2. Obter dados do usuário
$userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ch = curl_init($userInfoUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $tokenInfo['access_token']
]);

$userResponse = curl_exec($ch);
curl_close($ch);

$userInfo = json_decode($userResponse, true);

if (!isset($userInfo['email'])) {
    die('Erro ao obter dados do usuário');
}

// 3. Dados do Google
$googleEmail = $userInfo['email'];
$googleNome = $userInfo['name'] ?? 'Usuário Google';
$googleId = $userInfo['id'];

// 4. Obter informações do cliente
$ip = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
$navegador = $_SERVER['HTTP_USER_AGENT'] ?? 'desconhecido';

function detectarSistemaOperacional($userAgent) {
    if (stripos($userAgent, 'Windows') !== false) return 'Windows';
    if (stripos($userAgent, 'Linux') !== false) return 'Linux';
    if (stripos($userAgent, 'Mac') !== false) return 'macOS';
    if (stripos($userAgent, 'Android') !== false) return 'Android';
    if (stripos($userAgent, 'iPhone') !== false) return 'iOS';
    return 'Desconhecido';
}

$sistemaOperacional = detectarSistemaOperacional($navegador);

// 5. Corrigir problema do preg_replace (desabilitar JIT)
ini_set('pcre.jit', '0');

// 6. Verificar/inserir usuário
try {
    // Verifica se usuário já existe pelo email OU google_id
    $stmt = $conn->prepare("SELECT id, nome_completo, usuario, email FROM usuarios WHERE email = ? OR google_id = ?");
    $stmt->execute([$googleEmail, $googleId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Gera username
        $baseUsername = explode('@', $googleEmail)[0];
        $baseUsername = preg_replace('/[^a-z0-9]/i', '', $baseUsername);
        $username = strtolower($baseUsername) . rand(100, 999);
        
        // Verifica se username já existe
        $checkStmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $checkStmt->execute([$username]);
        $counter = 1;
        
        while ($checkStmt->fetch()) {
            $username = strtolower($baseUsername) . rand(1000, 9999);
            $checkStmt->execute([$username]);
            $counter++;
            if ($counter > 10) {
                $username = 'user' . rand(10000, 99999);
                break;
            }
        }
        
        try {
            $stmt = $conn->prepare("
                INSERT INTO usuarios 
                (nome_completo, email, usuario, senha_hash, ip_cadastro, navegador, sistema_operacional, provider, google_id, criado_em) 
                VALUES (?, ?, ?, '[GOOGLE_LOGIN]', ?, ?, ?, 'google', ?, NOW())
            ");
            
            $stmt->execute([
                $googleNome,
                $googleEmail,
                $username,
                $ip,
                $navegador,
                $sistemaOperacional,
                $googleId
            ]);
            
        } catch (PDOException $e) {
            die("ERRO ao criar usuário: " . $e->getMessage());
        }
        
        $userId = $conn->lastInsertId();
        
        // Busca dados do novo usuário
        $stmt = $conn->prepare("SELECT id, nome_completo, usuario, email FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            die("ERRO: Não foi possível recuperar usuário após criação");
        }
        
    } else {
        // Garantir que o email existe no array
        if (!isset($user['email'])) {
            $user['email'] = $googleEmail;
        }
        
        // Se usuário existe mas não tem google_id, atualiza
        $updateStmt = $conn->prepare("UPDATE usuarios SET google_id = ?, provider = 'google' WHERE id = ?");
        $updateStmt->execute([$googleId, $user['id']]);
    }
    
    // 7. Criar sessão
    session_start();
    $_SESSION['usuario_id'] = $user['id'];
    $_SESSION['usuario_nome'] = $user['usuario']; // PADRONIZADO: usa 'usuario' como no login.php
    $_SESSION['usuario'] = $user['usuario'];
    $_SESSION['usuario_email'] = $user['email'];
    $_SESSION['logado'] = true;
    $_SESSION['login_provider'] = 'google';
    $_SESSION['ultima_atividade'] = time();
    $_SESSION['criado_em'] = time();
    
    // 8. SISTEMA DE SESSÃO ÚNICA (IGUAL AO LOGIN LOCAL)
    // Remove TODAS as entradas anteriores deste usuário
    $stmt = $conn->prepare("DELETE FROM usuarios_online WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    
    // Pega o ID da sessão atual
    $session_id = session_id();
    
    // Insere o novo registro com session_id
    $stmt = $conn->prepare("
        INSERT INTO usuarios_online 
        (user_id, nome_completo, email, usuario, ip, navegador, sistema_operacional, 
         session_id, login_time, last_activity, provider, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 'google', 'online')
    ");
    
    $stmt->execute([
        $user['id'],
        $user['nome_completo'],
        $user['email'],
        $user['usuario'],
        $ip,
        $navegador,
        $sistemaOperacional,
        $session_id
    ]);
    
    // 9. Redireciona
    header('Location: /Hackers_Brasil_New/public/painel/Bem_Vindo_Ciberguranca.php');
    exit;
    
} catch (PDOException $e) {
    die("ERRO no banco PostgreSQL: " . $e->getMessage());
}
?>
