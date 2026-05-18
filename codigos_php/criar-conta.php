<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('America/Sao_Paulo');

require_once 'conexao.php';
require_once 'enviar_email_token_cadastro.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- RATE LIMITING ---
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
    $limite = 5; // tentativas permitidas
    $janela_segundos = 3600; // 1 hora

    $check = $conn->prepare("SELECT tentativas, ultimo_acesso FROM tentativas_cadastro WHERE ip = :ip");
    $check->execute(['ip' => $ip]);
    $linha = $check->fetch(PDO::FETCH_ASSOC);

    if ($linha) {
        $ultimo = strtotime($linha['ultimo_acesso']);
        $agora = time();

        if ($agora - $ultimo < $janela_segundos && $linha['tentativas'] >= $limite) {
            echo "Você atingiu o limite de tentativas. Tente novamente mais tarde.";
            exit;
        } elseif ($agora - $ultimo >= $janela_segundos) {
            // resetar contagem após 1 hora
            $update = $conn->prepare("UPDATE tentativas_cadastro SET tentativas = 1, ultimo_acesso = NOW() WHERE ip = :ip");
            $update->execute(['ip' => $ip]);
        } else {
            // aumentar contagem
            $update = $conn->prepare("UPDATE tentativas_cadastro SET tentativas = tentativas + 1, ultimo_acesso = NOW() WHERE ip = :ip");
            $update->execute(['ip' => $ip]);
        }
    } else {
        // inserir novo registro
        $insert = $conn->prepare("INSERT INTO tentativas_cadastro (ip, tentativas, ultimo_acesso) VALUES (:ip, 1, NOW())");
        $insert->execute(['ip' => $ip]);
    }

    // --- DADOS DO FORMULÁRIO ---
    $nome     = trim($_POST['nome_completo'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $usuario  = trim($_POST['usuario'] ?? '');
    $senha    = trim($_POST['senha'] ?? '');

    if (empty($nome) || empty($email) || empty($usuario) || empty($senha)) {
        echo "Preencha todos os campos.";
        exit;
    }

    // --- TOKEN E SENHA ---
    $token = strval(random_int(100000, 999999));
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    // --- DADOS DO DISPOSITIVO ---
    $navegador = $_SERVER['HTTP_USER_AGENT'] ?? 'desconhecido';

    function getSistemaOperacional($ua) {
        if (stripos($ua, 'Windows') !== false) return 'Windows';
        if (stripos($ua, 'Linux') !== false) return 'Linux';
        if (stripos($ua, 'Mac') !== false) return 'MacOS';
        if (stripos($ua, 'Android') !== false) return 'Android';
        if (stripos($ua, 'iPhone') !== false) return 'iOS';
        return 'Desconhecido';
    }
    $sistema = getSistemaOperacional($navegador);

    // --- VERIFICAR DUPLICIDADE ---
    $verifica = $conn->prepare("SELECT 1 FROM usuarios_pendentes WHERE email = :email OR usuario = :usuario");
    $verifica->execute(['email' => $email, 'usuario' => $usuario]);
    if ($verifica->fetch()) {
        echo "Email ou nome de usuário já cadastrados.";
        exit;
    }

    // --- INSERIR NO BANCO ---
    $sql = "INSERT INTO usuarios_pendentes 
        (nome_completo, email, usuario, senha_hash, token, ip_cadastro, navegador, sistema_operacional, token_expira_em)
        VALUES 
        (:nome, :email, :usuario, :senha_hash, :token, :ip, :navegador, :sistema, NOW() + INTERVAL '1 hour')";
    $stmt = $conn->prepare($sql);

    $ok = $stmt->execute([
        'nome' => $nome,
        'email' => $email,
        'usuario' => $usuario,
        'senha_hash' => $senhaHash,
        'token' => $token,
        'ip' => $ip,
        'navegador' => $navegador,
        'sistema' => $sistema
    ]);

    if ($ok) {
        if (enviarEmailConfirmacao($email, $nome, $token)) {
            echo "sucesso";
        } else {
            echo "Erro ao enviar e-mail.";
        }
    } else {
        echo "Erro ao salvar cadastro.";
    }
}
?>

