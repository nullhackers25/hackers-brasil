<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('America/Sao_Paulo');
require_once 'conexao.php';
require_once 'config.php';

// Carregar .env
$env = parse_ini_file(__DIR__ . '/../.env');
$turnstile_secret = $env['TURNSTILE_SECRET_KEY'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario_input = trim($_POST['usuario'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
    $navegador = $_SERVER['HTTP_USER_AGENT'] ?? 'desconhecido';

    // Função para detectar o sistema operacional
    function getSistemaOperacional($ua) {
        if (stripos($ua, 'Windows') !== false) return 'Windows';
        if (stripos($ua, 'Linux') !== false) return 'Linux';
        if (stripos($ua, 'Mac') !== false) return 'macOS';
        if (stripos($ua, 'Android') !== false) return 'Android';
        if (stripos($ua, 'iPhone') !== false) return 'iOS';
        return 'Desconhecido';
    }

    $sistema_operacional = getSistemaOperacional($navegador);
    
   // ===== VALIDAÇÃO TURNSTILE =====
   $turnstile_token = $_POST['cf-turnstile-response'] ?? '';

   if (empty($turnstile_token)) {
       echo "Verificação de segurança falhou. Tente novamente.";
       exit;
   }

   // Verificar token com Cloudflare
   $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   curl_setopt($ch, CURLOPT_POST, true);
   curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
       'secret' => TURNSTILE_SECRET_KEY,
       'response' => $turnstile_token,
       'remoteip' => $ip
   ]));

   $response = curl_exec($ch);
   curl_close($ch);

   $result = json_decode($response, true);

   if (!$result['success']) {
       echo "Verificação de segurança falhou. Tente novamente.";
       exit;
   }
   // ===== FIM VALIDAÇÃO TURNSTILE =====

    // Verificando se os campos de login foram preenchidos
    if (empty($usuario_input) || empty($senha)) {
        echo "Preencha todos os campos.";
        exit;
    }

    // Limitando as tentativas de login
    $limite = 5;
    $check = $conn->prepare("
        SELECT COUNT(*) 
        FROM tentativas_login
        WHERE ip = :ip
        AND created_at > NOW() - INTERVAL '1 hour'
        AND type = 'LOGIN_FAIL'
    ");
    $check->execute(['ip' => $ip]);
    $tentativas_recentes = $check->fetchColumn();

    if ($tentativas_recentes >= $limite) {
        echo "Você atingiu o limite de tentativas. Tente novamente mais tarde.";
        exit;
    }

    // Verificando se o IP está bloqueado
    $stmt = $conn->prepare("
        SELECT 1 FROM logins_bloqueados
        WHERE ip = :ip
        AND blocked_until > NOW()
    ");
    $stmt->execute(['ip' => $ip]);

    if ($stmt->fetch()) {
        echo "Acesso bloqueado temporariamente.";
        exit;
    }

    // ===== VERIFICANDO SE O USUÁRIO EXISTE E SE ESTÁ BLOQUEADO =====
    $stmt = $conn->prepare("
        SELECT id, nome_completo, usuario, email, senha_hash, bloqueado_ate 
        FROM usuarios
        WHERE usuario = :usuario OR email = :usuario
        LIMIT 1
    ");
    $stmt->execute(['usuario' => $usuario_input]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // ===== VERIFICAÇÃO DE BLOQUEIO DO USUÁRIO =====
    if ($user) {
        // Verificar se o usuário está bloqueado
        if ($user['bloqueado_ate']) {
            $timestampBloqueio = strtotime($user['bloqueado_ate']);
            $timestampAtual = time();
            
            if ($timestampBloqueio > $timestampAtual) {
                // Usuário está bloqueado
                $dataBloqueio = date('d/m/Y H:i', $timestampBloqueio);
                
                // Registrar tentativa de login de usuário bloqueado
                $stmt = $conn->prepare("
                    INSERT INTO tentativas_login
                    (user_identifier, user_id, ip, navegador, sistema_operacional, type, details)
                    VALUES (:user_identifier, :user_id, :ip, :navegador, :sistema_operacional, 'LOGIN_BLOCKED', :details)
                ");
                $stmt->execute([
                    'user_identifier' => $usuario_input,
                    'user_id' => $user['id'],
                    'ip' => $ip,
                    'navegador' => $navegador,
                    'sistema_operacional' => $sistema_operacional,
                    'details' => json_encode([
                        'bloqueado_ate' => $user['bloqueado_ate'],
                        'mensagem' => 'Usuário bloqueado'
                    ])
                ]);
                
                echo "Usuário bloqueado até $dataBloqueio";
                exit;
            } else {
                // Bloqueio expirou - limpar campo
                $stmt = $conn->prepare("UPDATE usuarios SET bloqueado_ate = NULL WHERE id = :id");
                $stmt->execute(['id' => $user['id']]);
                $user['bloqueado_ate'] = null;
            }
        }
    }

    // Se a senha estiver incorreta ou usuário não existe
    if (!$user || !password_verify($senha, $user['senha_hash'])) {
        $stmt = $conn->prepare("
            INSERT INTO tentativas_login
            (user_identifier, password_identifier, user_id, ip, navegador, sistema_operacional, type, details)
            VALUES (:user_identifier, :pwd, :user_id, :ip, :navegador, :sistema_operacional, 'LOGIN_FAIL', :details)
        ");
        $stmt->execute([
            'user_identifier' => $usuario_input,
            'pwd' => $senha,
            'user_id' => $user['id'] ?? null,
            'ip' => $ip,
            'navegador' => $navegador,
            'sistema_operacional' => $sistema_operacional,
            'details' => json_encode(['fail_count' => $tentativas_recentes + 1])
        ]);

        // Se as tentativas de login ultrapassaram o limite, bloqueia o IP
        if ($tentativas_recentes + 1 >= $limite) {
            $stmt = $conn->prepare("
                INSERT INTO logins_bloqueados
                (ip, navegador, sistema_operacional, blocked_until, reason)
                VALUES (:ip, :navegador, :sistema_operacional, NOW() + INTERVAL '30 minutes', 'Tentativas consecutivas excedidas')
            ");
            $stmt->execute([
                'ip' => $ip,
                'navegador' => $navegador,
                'sistema_operacional' => $sistema_operacional
            ]);
            echo "Você excedeu o número de tentativas.";
        } else {
            echo "Usuário ou senha incorretos.";
        }
        exit;
    }

    // ===== LOGIN SUCESSO =====
    // Registrar tentativa de login bem-sucedida
    $stmt = $conn->prepare("
        INSERT INTO tentativas_login
        (user_identifier, user_id, ip, navegador, sistema_operacional, type, details)
        VALUES (:user_identifier, :user_id, :ip, :navegador, :sistema_operacional, 'LOGIN_SUCCESS', :details)
    ");
    $stmt->execute([
        'user_identifier' => $usuario_input,
        'user_id' => $user['id'],
        'ip' => $ip,
        'navegador' => $navegador,
        'sistema_operacional' => $sistema_operacional,
        'details' => json_encode(['with_2fa' => false])
    ]);

    // Criando sessão de login
    session_start();
    $_SESSION['usuario_id']   = $user['id'];
    $_SESSION['usuario_nome'] = $user['usuario'];
    $_SESSION['usuario_email'] = $user['email'] ?? '';
    $_SESSION['logado']       = true;
    $_SESSION['login_provider'] = 'local';
    $_SESSION['ultima_atividade'] = time();
    $_SESSION['criado_em'] = time();

    // Se o usuário estava bloqueado (mas o tempo expirou), limpar o histórico
    if ($user['bloqueado_ate']) {
        $stmt = $conn->prepare("
            INSERT INTO bloqueios_usuarios 
            (usuario_id, motivo, bloqueado_ate, tipo)
            VALUES (:usuario_id, :motivo, :bloqueado_ate, 'expirado')
        ");
        $stmt->execute([
            'usuario_id' => $user['id'],
            'motivo' => 'Bloqueio expirado - login permitido',
            'bloqueado_ate' => $user['bloqueado_ate']
        ]);
        
        // Atualizar para NULL após registrar o histórico
        $stmt = $conn->prepare("UPDATE usuarios SET bloqueado_ate = NULL WHERE id = :id");
        $stmt->execute(['id' => $user['id']]);
    }

    // ===== ANTES DE REGISTRAR O NOVO LOGIN, REMOVE OS ANTIGOS =====
    try {
        // Remove todas as entradas anteriores deste usuário
        $stmt = $conn->prepare("DELETE FROM usuarios_online WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $user['id']]);
        
        // Log para auditoria
        error_log("Usuário {$user['id']} deslogado de dispositivos anteriores. Novo login de IP: $ip");
        
    } catch (PDOException $e) {
        error_log("Erro ao remover sessões anteriores: " . $e->getMessage());
        // Continua mesmo com erro - tenta fazer o novo login
    }

    // ===== REGISTRA O NOVO LOGIN COM SESSION_ID =====
    $email = $user['email'] ?? '';
    $nome_completo = $user['nome_completo'] ?? '';  
    $usuario = $user['usuario'] ?? '';
    $session_id = session_id(); // Pega o ID da sessão atual

    $stmt = $conn->prepare("
        INSERT INTO usuarios_online
        (user_id, nome_completo, email, usuario, ip, navegador, sistema_operacional, 
         session_id, last_activity, login_time, provider, status)
        VALUES 
        (:user_id, :nome_completo, :email, :usuario, :ip, :navegador, :sistema_operacional,
         :session_id, NOW(), NOW(), 'local', 'online')
    ");

    $success = $stmt->execute([
        'user_id' => $user['id'],
        'nome_completo' => $nome_completo,
        'email' => $email,
        'usuario' => $usuario,
        'ip' => $ip,
        'navegador' => $navegador,
        'sistema_operacional' => $sistema_operacional,
        'session_id' => $session_id
    ]);

    if (!$success) {
        error_log("Erro ao inserir em usuarios_online: " . implode(", ", $stmt->errorInfo())); 
    }

    echo "sucesso";
}
?>
