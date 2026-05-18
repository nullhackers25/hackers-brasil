<?php
require_once 'conexao.php';
require_once 'enviar_email_token_cadastro.php';
date_default_timezone_set('America/Sao_Paulo');

// Define o header JSON
header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Método inválido.']);
        exit;
    }

    $email = trim($_POST['email'] ?? '');

    // Verifica se o e-mail foi preenchido
    if (empty($email)) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Preencha o e-mail!']);
        exit;
    }

    // Valida formato de e-mail
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'E-mail inválido!']);
        exit;
    }

    // Verifica se o e-mail existe na tabela usuarios_pendentes
    $stmt = $conn->prepare("SELECT * FROM usuarios_pendentes WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'E-mail não encontrado ou já confirmado.']);
        exit;
    }

    // Limita reenvio do token: só pode reenviar a cada 1 minuto
    $ultimoReenvio = strtotime($usuario['token_expira_em']) - 3600; // último token gerado
    if (time() - $ultimoReenvio < 60) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Aguarde 1 minuto antes de reenviar o token.']);
        exit;
    }

    // Gera novo token
    $novoToken = strval(random_int(100000, 999999));
    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Atualiza token no banco
    $update = $conn->prepare("UPDATE usuarios_pendentes SET token = :token, token_expira_em = :expira WHERE id = :id");
    $update->execute([
        'token' => $novoToken,
        'expira' => $expira,
        'id' => $usuario['id']
    ]);

    // Envia e-mail com o novo token
    if (enviarEmailConfirmacao($email, $usuario['nome_completo'], $novoToken)) {
        echo json_encode(['status' => 'sucesso', 'mensagem' => 'Token reenviado com sucesso! Verifique seu e-mail.']);
    } else {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao enviar e-mail.']);
    }

} catch (Exception $e) {
    // Em caso de erro inesperado
    echo json_encode(['status' => 'erro', 'mensagem' => 'Ocorreu um erro. Tente novamente.']);
    error_log("Erro reenviar_token.php: " . $e->getMessage());
}
?>
