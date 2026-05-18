<?php
require_once 'conexao.php';
date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $token_digitado = trim($_POST['token'] ?? '');

    if (empty($email) || empty($token_digitado)) {
        echo json_encode(['status'=>'erro','mensagem'=>"Preencha todos os campos!"]);
        exit;
    }

    // Buscar usuário pendente com email + token
    $sql = "SELECT * FROM usuarios_pendentes WHERE email = :email AND token = :token";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'email' => $email,
        'token' => $token_digitado
    ]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        echo json_encode(['status'=>'erro','mensagem'=>"Token inválido."]);
        exit;
    }

    // Verificar se o token expirou
    if (strtotime($usuario['token_expira_em']) < time()) {
        echo json_encode(['status'=>'erro','mensagem'=>"Token expirado."]);
        exit;
    }

    // Inserir na tabela 'usuarios'
    $sqlInsert = "INSERT INTO usuarios 
        (nome_completo, email, usuario, senha_hash, ip_cadastro, navegador, sistema_operacional)
        VALUES (:nome, :email, :usuario, :senha_hash, :ip, :navegador, :sistema)";
    $stmtInsert = $conn->prepare($sqlInsert);
    $ok = $stmtInsert->execute([
        'nome'      => $usuario['nome_completo'],
        'email'     => $usuario['email'],
        'usuario'   => $usuario['usuario'],
        'senha_hash'=> $usuario['senha_hash'],
        'ip'        => $usuario['ip_cadastro'],
        'navegador' => $usuario['navegador'],
        'sistema'   => $usuario['sistema_operacional']
    ]);

    if (!$ok) {
        echo json_encode(['status'=>'erro','mensagem'=>"Erro ao criar conta."]);
        exit;
    }

    // Deletar da tabela de pendentes
    $stmtDelete = $conn->prepare("DELETE FROM usuarios_pendentes WHERE id = :id");
    $stmtDelete->execute(['id' => $usuario['id']]);

    // Retorna sucesso e redirecionamento
    echo json_encode([
        'status' => 'sucesso',
        'mensagem' => "Conta criada com sucesso! Redirecionando..."
    ]);
}
?>
