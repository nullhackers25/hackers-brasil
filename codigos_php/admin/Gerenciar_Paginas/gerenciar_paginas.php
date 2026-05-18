<?php
session_start();
require_once '../../conexao.php';

// Impedir acesso sem login
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login_admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Páginas</title>
    <link rel="stylesheet" href="../admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="container">
    <h1>Gerenciar Páginas</h1>

    <p>Aqui você pode visualizar todas as páginas do site, editar seus conteúdos, criar novas páginas ou organizar a estrutura do menu. Atualmente existem <strong><?= $total_paginas ?> páginas</strong> no sistema.</p>
       
    <div class="botoes-painel">
         <a href="lista_paginas.php">Listar Páginas</a>
        
         <a href="../admin.php">Voltar ao Painel</a>

         <a href="../logout.php" class="logout">Sair</a>
    </div>
</div>

</body>
</html>
