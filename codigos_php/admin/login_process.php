<?php
session_start();
require_once '../conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login_admin.php");
    exit;
}

$usuario = trim($_POST['usuario'] ?? '');
$senha   = trim($_POST['senha'] ?? '');

if ($usuario === "" || $senha === "") {
    echo "Preencha todos os campos.";
    exit;
}

$sql = "SELECT * FROM admin_usuarios WHERE usuario = :usuario";
$stmt = $conn->prepare($sql);
$stmt->execute(['usuario' => $usuario]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    echo "Usuário não encontrado.";
    exit;
}

if (!password_verify($senha, $admin['senha_hash'])) {
    echo "Senha incorreta.";
    exit;
}

$_SESSION['admin_id'] = $admin['id'];
$_SESSION['admin_usuario'] = $admin['usuario'];

header("Location: admin.php");
exit;
