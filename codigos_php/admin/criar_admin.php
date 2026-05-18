<?php
require_once '../conexao.php';

$usuario = "admin";
$senha = "admin123"; // depois você troca por outra mais segura

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO admin_usuarios (usuario, senha_hash) VALUES (:usuario, :senha)");
$stmt->execute([
    'usuario' => $usuario,
    'senha' => $senhaHash
]);

echo "Administrador criado com sucesso!";
