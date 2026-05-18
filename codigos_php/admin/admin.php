<?php
session_start();

// Impede acesso sem login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel Admin Hackerts Brasil</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

    <h1>Painel de Controle</h1>

    <p>
        Bem-vindo ao painel do Hackers Brasil, 
        <strong><?php echo htmlspecialchars($_SESSION['admin_usuario']); ?></strong>.<br>
        Aqui você gerencia usuários cadastrados, administradores páginas e configurações internas do sistema.
    </p>

    <div class="botoes-painel">
        <a href="usuarios.php">Usuários</a>
        <a href="administradores.php">Administradores</a>
        <a href="gerenciar_tabelas.php">Gerenciar Tabelas</a>
        <a href="Gerenciar_Paginas/gerenciar_paginas.php"> Gerenciar Páginas</a>
        <a href="logout.php" class="logout">Sair</a>
    </div>

</body>
</html>
