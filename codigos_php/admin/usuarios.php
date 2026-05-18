<?php
session_start();
require_once '../conexao.php';

// Impede acesso sem login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Usuários</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<h1>Gerenciar Usuários</h1>
<p>Escolha abaixo qual tipo de cadastro deseja visualizar ou editar.</p>

<div class="botoes-painel">

    <a href="usuarios_bank.php">Usuários Ativos</a>
    
    <a href="usuarios_bloqueados.php">Usuários Bloqueados</a> 

    <a href="usuarios_online.php">Usuários Online</a> 
    
    <a href="logins_bloqueados.php">Logins Bloqueados</a>

    <a href="usuarios_pendentes_bank.php">Usuários Pendentes</a>

    <a href="admin.php">Voltar ao Painel</a>

    <a href="logout.php" class="logout">Sair</a>

</div>

</body>
</html>
