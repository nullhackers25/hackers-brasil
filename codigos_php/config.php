<?php
// Define timezone padrão para evitar erros de hora
date_default_timezone_set('America/Sao_Paulo');

// ============================================
// AMBIENTE: Local vs Produção (Render)
// ============================================
$is_production = getenv('RENDER') === 'true';

if ($is_production) {
    // ============================================
    // MODO PRODUÇÃO (RENDER)
    // ============================================
    
    // Conexão com NeonDB via DATABASE_URL (Render fornece)
    $database_url = getenv('DATABASE_URL');
    if ($database_url) {
        $db_config = parse_url($database_url);
        
        define('DB_HOST', $db_config['host']);
        define('DB_NAME', ltrim($db_config['path'], '/'));
        define('DB_USER', $db_config['user']);
        define('DB_PASS', $db_config['pass'] ?? '');
        define('DB_PORT', $db_config['port'] ?? 5432);
    } else {
        // Fallback para valores diretos (se não usar DATABASE_URL)
        define('DB_HOST', getenv('DB_HOST') ?: 'ep-ancient-frog-ad8rwyp2-pooler.c-2.us-east-1.aws.neon.tech');
        define('DB_NAME', getenv('DB_NAME') ?: 'neondb');
        define('DB_USER', getenv('DB_USER') ?: 'neondb_owner');
        define('DB_PASS', getenv('DB_PASS') ?: '');
        define('DB_PORT', getenv('DB_PORT') ?: 5432);
    }
    
    // E-mail (Gmail no Render também funciona)
    define('MAIL_HOST', getenv('MAIL_HOST') ?: 'smtp.gmail.com');
    define('MAIL_PORT', getenv('MAIL_PORT') ?: 587);
    define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: 'portalhackersbrasil@gmail.com');
    define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: '');
    define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'Portal Hackers Brasil');
    
    // Google OAuth
    define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
    define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
    define('GOOGLE_REDIRECT_URI', getenv('GOOGLE_REDIRECT_URI') ?: 'https://hackers-brasil.onrender.com/codigos_php/google_callback.php');
    
    // Cloudflare Turnstile
    define('TURNSTILE_SITE_KEY', getenv('TURNSTILE_SITE_KEY') ?: '');
    define('TURNSTILE_SECRET_KEY', getenv('TURNSTILE_SECRET_KEY') ?: '');
    
    // Supabase (Storage para imagens)
    define('SUPABASE_URL', getenv('SUPABASE_URL') ?: '');
    define('SUPABASE_ANON_KEY', getenv('SUPABASE_ANON_KEY') ?: '');
    define('SUPABASE_BUCKET', getenv('SUPABASE_BUCKET') ?: 'imagens');
    
} else {
    // ============================================
    // MODO LOCAL (Desenvolvimento)
    // ============================================
    
    // Carrega variáveis do .env (apenas local)
    $env_file = __DIR__ . '/.env';
    if (file_exists($env_file)) {
        $env = parse_ini_file($env_file);
    } else {
        $env = [];
    }
    
    // Banco de dados (NeonDB)
    define('DB_HOST', $env['DB_HOST'] ?? 'ep-ancient-frog-ad8rwyp2-pooler.c-2.us-east-1.aws.neon.tech');
    define('DB_NAME', $env['DB_NAME'] ?? 'neondb');
    define('DB_USER', $env['DB_USER'] ?? 'neondb_owner');
    define('DB_PASS', $env['DB_PASS'] ?? '');
    define('DB_PORT', $env['DB_PORT'] ?? 5432);
    
    // E-mail (Gmail)
    define('MAIL_HOST', $env['MAIL_HOST'] ?? 'smtp.gmail.com');
    define('MAIL_PORT', $env['MAIL_PORT'] ?? 587);
    define('MAIL_USERNAME', $env['MAIL_USERNAME'] ?? 'portalhackersbrasil@gmail.com');
    define('MAIL_PASSWORD', $env['MAIL_PASSWORD'] ?? '');
    define('MAIL_FROM_NAME', $env['MAIL_FROM_NAME'] ?? 'Portal Hackers Brasil');
    
    // Google OAuth
    define('GOOGLE_CLIENT_ID', $env['GOOGLE_CLIENT_ID'] ?? '');
    define('GOOGLE_CLIENT_SECRET', $env['GOOGLE_CLIENT_SECRET'] ?? '');
    define('GOOGLE_REDIRECT_URI', $env['GOOGLE_REDIRECT_URI'] ?? 'http://127.0.0.1:8080/Hackers_Brasil_New/codigos_php/google_callback.php');
    
    // Cloudflare Turnstile
    define('TURNSTILE_SITE_KEY', $env['TURNSTILE_SITE_KEY'] ?? '');
    define('TURNSTILE_SECRET_KEY', $env['TURNSTILE_SECRET_KEY'] ?? '');
    
    // Supabase (Storage)
    define('SUPABASE_URL', $env['SUPABASE_URL'] ?? '');
    define('SUPABASE_ANON_KEY', $env['SUPABASE_ANON_KEY'] ?? '');
    define('SUPABASE_BUCKET', $env['SUPABASE_BUCKET'] ?? 'imagens');
}

// ============================================
// CONEXÃO COM O BANCO DE DADOS (PDO - PostgreSQL)
// ============================================
try {
    // String de conexão PostgreSQL com SSL obrigatório para NeonDB
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require";
    
    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
} catch (PDOException $e) {
    // Log do erro (sem exibir detalhes em produção)
    error_log("Erro de conexão com banco de dados: " . $e->getMessage());
    
    if ($is_production) {
        die("Erro interno. Tente novamente mais tarde.");
    } else {
        die("Erro de conexão: " . $e->getMessage());
    }
}

// ============================================
// FUNÇÕES SUPABASE (Storage para imagens)
// ============================================
function uploadImagemSupabase($arquivo_tmp, $caminho_destino, $content_type = null) {
    if (!defined('SUPABASE_URL') || !SUPABASE_URL || !defined('SUPABASE_ANON_KEY') || !SUPABASE_ANON_KEY) {
        error_log("Supabase não configurado");
        return false;
    }
    
    $url = SUPABASE_URL . "/storage/v1/object/" . SUPABASE_BUCKET . "/" . $caminho_destino;
    $content_type = $content_type ?: mime_content_type($arquivo_tmp);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($arquivo_tmp));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Content-Type: ' . $content_type
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return SUPABASE_URL . "/storage/v1/object/public/" . SUPABASE_BUCKET . "/" . $caminho_destino;
    }
    
    error_log("Erro upload Supabase: HTTP $httpCode - " . substr($response, 0, 200));
    return false;
}

function deletarImagemSupabase($caminho) {
    if (!defined('SUPABASE_URL') || !SUPABASE_URL || !defined('SUPABASE_ANON_KEY') || !SUPABASE_ANON_KEY) {
        return false;
    }
    
    $url = SUPABASE_URL . "/storage/v1/object/" . SUPABASE_BUCKET . "/" . $caminho;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . SUPABASE_ANON_KEY
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}

// ============================================
// FUNÇÕES AUXILIARES
// ============================================
function isProduction() {
    return getenv('RENDER') === 'true';
}

function getBaseUrl() {
    if (isProduction()) {
        return 'https://' . getenv('RENDER_EXTERNAL_HOSTNAME');
    }
    return 'http://127.0.0.1:8080/Hackers_Brasil_New';
}

// Função para enviar e-mail (Gmail/SMTP)
function enviarEmail($destinatario, $assunto, $corpoHtml, $corpoTexto = '') {
    require_once ROOT_PATH . '/codigos_php/PHPMailer/src/Exception.php';
    require_once ROOT_PATH . '/codigos_php/PHPMailer/src/PHPMailer.php';
    require_once ROOT_PATH . '/codigos_php/PHPMailer/src/SMTP.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        
        $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
        $mail->addAddress($destinatario);
        
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body    = $corpoHtml;
        $mail->AltBody = $corpoTexto ?: strip_tags($corpoHtml);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erro ao enviar e-mail: " . $mail->ErrorInfo);
        return false;
    }
}
?>
