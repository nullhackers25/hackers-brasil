<?php
session_start();
require_once '../conexao.php';

// Impedir acesso sem login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Buscar todos os administradores
$sql = "SELECT id, usuario, nome_completo, email, ip_permitido, ativo, 
               nivel_acesso, criado_em, ultimo_login, bloqueado 
        FROM admin_usuarios 
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$administradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar ações via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => ''];
    
    // ADICIONAR NOVO ADMINISTRADOR
    if ($_POST['action'] === 'add_admin') {
        $usuario = trim($_POST['usuario'] ?? '');
        $nome_completo = trim($_POST['nome_completo'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';
        $ip_permitido = trim($_POST['ip_permitido'] ?? '');
        $nivel_acesso = $_POST['nivel_acesso'] ?? 'admin';
        
        // Validações
        if (empty($usuario) || empty($nome_completo) || empty($email) || empty($senha)) {
            $response['message'] = 'Todos os campos obrigatórios devem ser preenchidos!';
        } elseif ($senha !== $confirmar_senha) {
            $response['message'] = 'As senhas não coincidem!';
        } elseif (strlen($senha) < 6) {
            $response['message'] = 'A senha deve ter pelo menos 6 caracteres!';
        } else {
            try {
                // Verificar se usuário já existe
                $check_sql = "SELECT id FROM admin_usuarios WHERE usuario = :usuario OR email = :email";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bindParam(':usuario', $usuario);
                $check_stmt->bindParam(':email', $email);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    $response['message'] = 'Usuário ou email já cadastrado!';
                } else {
                    // Hash da senha
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                    
                    // Inserir novo administrador
                    $insert_sql = "INSERT INTO admin_usuarios 
                                   (usuario, nome_completo, email, senha_hash, ip_permitido, nivel_acesso, criado_em) 
                                   VALUES (:usuario, :nome_completo, :email, :senha_hash, :ip_permitido, :nivel_acesso, NOW())";
                    
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bindParam(':usuario', $usuario);
                    $insert_stmt->bindParam(':nome_completo', $nome_completo);
                    $insert_stmt->bindParam(':email', $email);
                    $insert_stmt->bindParam(':senha_hash', $senha_hash);
                    $insert_stmt->bindParam(':ip_permitido', $ip_permitido);
                    $insert_stmt->bindParam(':nivel_acesso', $nivel_acesso);
                    $insert_stmt->execute();
                    
                    $response['success'] = true;
                    $response['message'] = 'Administrador adicionado com sucesso!';
                    $response['reload'] = true;
                }
            } catch (PDOException $e) {
                $response['message'] = 'Erro ao adicionar administrador: ' . $e->getMessage();
            }
        }
        
        echo json_encode($response);
        exit;
    }
    
    // EDITAR ADMINISTRADOR
    if ($_POST['action'] === 'edit_admin') {
        $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
        $usuario = trim($_POST['usuario'] ?? '');
        $nome_completo = trim($_POST['nome_completo'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $ip_permitido = trim($_POST['ip_permitido'] ?? '');
        $nivel_acesso = $_POST['nivel_acesso'] ?? 'admin';
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        
        if ($id && $usuario && $nome_completo && $email) {
            try {
                // Verificar se outro administrador já tem este usuário ou email
                $check_sql = "SELECT id FROM admin_usuarios 
                              WHERE (usuario = :usuario OR email = :email) AND id != :id";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bindParam(':usuario', $usuario);
                $check_stmt->bindParam(':email', $email);
                $check_stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    $response['message'] = 'Usuário ou email já está em uso por outro administrador!';
                } else {
                    // Atualizar dados
                    $update_sql = "UPDATE admin_usuarios SET 
                                   usuario = :usuario,
                                   nome_completo = :nome_completo,
                                   email = :email,
                                   ip_permitido = :ip_permitido,
                                   nivel_acesso = :nivel_acesso,
                                   ativo = :ativo 
                                   WHERE id = :id";
                    
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bindParam(':usuario', $usuario);
                    $update_stmt->bindParam(':nome_completo', $nome_completo);
                    $update_stmt->bindParam(':email', $email);
                    $update_stmt->bindParam(':ip_permitido', $ip_permitido);
                    $update_stmt->bindParam(':nivel_acesso', $nivel_acesso);
                    $update_stmt->bindParam(':ativo', $ativo, PDO::PARAM_INT);
                    $update_stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $update_stmt->execute();
                    
                    $response['success'] = true;
                    $response['message'] = 'Administrador atualizado com sucesso!';
                }
            } catch (PDOException $e) {
                $response['message'] = 'Erro ao atualizar administrador: ' . $e->getMessage();
            }
        } else {
            $response['message'] = 'Dados inválidos!';
        }
        
        echo json_encode($response);
        exit;
    }
    
    // ATUALIZAR SENHA
    if ($_POST['action'] === 'update_password') {
        $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
        $nova_senha = $_POST['nova_senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';
        
        if ($id && $nova_senha && $confirmar_senha) {
            if ($nova_senha !== $confirmar_senha) {
                $response['message'] = 'As senhas não coincidem!';
            } elseif (strlen($nova_senha) < 6) {
                $response['message'] = 'A senha deve ter pelo menos 6 caracteres!';
            } else {
                try {
                    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    
                    $update_sql = "UPDATE admin_usuarios SET senha_hash = :senha_hash WHERE id = :id";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bindParam(':senha_hash', $senha_hash);
                    $update_stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $update_stmt->execute();
                    
                    $response['success'] = true;
                    $response['message'] = 'Senha atualizada com sucesso!';
                } catch (PDOException $e) {
                    $response['message'] = 'Erro ao atualizar senha: ' . $e->getMessage();
                }
            }
        } else {
            $response['message'] = 'Dados inválidos!';
        }
        
        echo json_encode($response);
        exit;
    }
    
    // REMOVER ADMINISTRADOR
    if ($_POST['action'] === 'delete_admin') {
        $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
        
        if ($id) {
            // Impedir remoção do próprio usuário
            if ($id == $_SESSION['admin_id']) {
                $response['message'] = 'Você não pode remover seu próprio usuário!';
            } else {
                try {
                    $delete_sql = "DELETE FROM admin_usuarios WHERE id = :id";
                    $delete_stmt = $conn->prepare($delete_sql);
                    $delete_stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $delete_stmt->execute();
                    
                    $response['success'] = true;
                    $response['message'] = 'Administrador removido com sucesso!';
                    $response['reload'] = true;
                } catch (PDOException $e) {
                    $response['message'] = 'Erro ao remover administrador: ' . $e->getMessage();
                }
            }
        } else {
            $response['message'] = 'ID inválido!';
        }
        
        echo json_encode($response);
        exit;
    }
    
    // BLOQUEAR/DESBLOQUEAR ADMINISTRADOR
    if ($_POST['action'] === 'toggle_block') {
        $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
        $bloquear = $_POST['bloquear'] ?? true;
        
        if ($id) {
            // Impedir bloquear a si mesmo
            if ($id == $_SESSION['admin_id'] && $bloquear) {
                $response['message'] = 'Você não pode bloquear seu próprio usuário!';
            } else {
                try {
                    $update_sql = "UPDATE admin_usuarios SET bloqueado = :bloqueado WHERE id = :id";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bindParam(':bloqueado', $bloquear, PDO::PARAM_INT);
                    $update_stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $update_stmt->execute();
                    
                    $acao = $bloquear ? 'bloqueado' : 'desbloqueado';
                    $response['success'] = true;
                    $response['message'] = "Administrador $acao com sucesso!";
                    $response['reload'] = true;
                } catch (PDOException $e) {
                    $response['message'] = 'Erro ao atualizar status: ' . $e->getMessage();
                }
            }
        }
        
        echo json_encode($response);
        exit;
    }
    
    // BUSCAR DADOS DO ADMINISTRADOR (para edição)
    if ($_POST['action'] === 'get_admin') {
        $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
        
        if ($id) {
            try {
                $sql = "SELECT id, usuario, nome_completo, email, ip_permitido, nivel_acesso, ativo 
                        FROM admin_usuarios 
                        WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($admin) {
                    $response['success'] = true;
                    $response['admin'] = $admin;
                } else {
                    $response['message'] = 'Administrador não encontrado!';
                }
            } catch (PDOException $e) {
                $response['message'] = 'Erro ao buscar dados: ' . $e->getMessage();
            }
        }
        
        echo json_encode($response);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Administradores</title>
    <link rel="stylesheet" href="usuarios_bank.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* Estilos específicos para administradores */
    .container {
        max-width: 1300px;
        margin: 0 auto;
        padding: 20px;
        background: #0d1117;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        margin-top: 20px;
    }
    
    .painel-acoes {
        display: flex;
        gap: 15px;
        margin-bottom: 25px;
        flex-wrap: wrap;
    }
    
    .painel-acoes .btn {
        padding: 12px 20px;
        border-radius: 8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        font-size: 0.95rem;
    }
    
    .btn.voltar {
        background: linear-gradient(135deg, #1b263b 0%, #24344d 100%);
        color: #dceaff;
        border: 1px solid #2a3a5a;
    }
    
    .btn.novo-admin {
        background: linear-gradient(135deg, #00cc66 0%, #00994d 100%);
        color: white;
        border: 1px solid #00dd77;
    }
    
    .btn.logout {
        background: linear-gradient(135deg, #ff4d4d 0%, #cc0000 100%);
        color: white;
        border: 1px solid #ff6666;
    }
    
    /* Badges de status */
    .badge-status {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .badge-ativo {
        background: linear-gradient(135deg, #00cc66 0%, #00994d 100%);
        color: white;
    }
    
    .badge-inativo {
        background: linear-gradient(135deg, #ff9900 0%, #cc6600 100%);
        color: white;
    }
    
    .badge-bloqueado {
        background: linear-gradient(135deg, #ff3333 0%, #cc0000 100%);
        color: white;
    }
    
    .badge-super {
        background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
        color: white;
    }
    
    .badge-admin {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        color: white;
    }
    
    /* Cards de estatísticas */
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #1b263b 0%, #24344d 100%);
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        border: 1px solid #2a3a5a;
        transition: transform 0.3s;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-icon {
        font-size: 2.5rem;
        margin-bottom: 10px;
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        margin: 10px 0;
    }
    
    .stat-label {
        color: #a0b3d6;
        font-size: 0.9rem;
    }
    
    /* Tabela */
    .table-container {
        overflow-x: auto;
        background: #1a2029;
        border-radius: 8px;
        padding: 15px;
    }
    
    .tabela {
        width: 100%;
        border-collapse: collapse;
    }
    
    .tabela th {
        background: linear-gradient(135deg, #1b263b 0%, #24344d 100%);
        color: #dceaff;
        padding: 15px;
        text-align: left;
        border-bottom: 2px solid #2a3a5a;
        font-weight: 600;
        white-space: nowrap;
    }
    
    .tabela td {
        padding: 15px;
        border-bottom: 1px solid #2a3a5a;
        color: #a0b3d6;
        vertical-align: middle;
    }
    
    .tabela tr:hover {
        background: rgba(77, 163, 255, 0.05);
    }
    
    .acoes {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .btn-acao {
        padding: 8px 12px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.3s;
        text-decoration: none;
    }
    
    .btn-acao.editar {
        background: linear-gradient(135deg, #4da3ff 0%, #2d8cff 100%);
        color: white;
    }
    
    .btn-acao.senha {
        background: linear-gradient(135deg, #ffcc00 0%, #e6b800 100%);
        color: #000;
    }
    
    .btn-acao.bloquear {
        background: linear-gradient(135deg, #ff9900 0%, #cc6600 100%);
        color: white;
    }
    
    .btn-acao.desbloquear {
        background: linear-gradient(135deg, #00cc66 0%, #00994d 100%);
        color: white;
    }
    
    .btn-acao.excluir {
        background: linear-gradient(135deg, #ff4d4d 0%, #cc0000 100%);
        color: white;
    }
    
    /* Modais */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    
    .modal-content {
        background: #1a2029;
        border-radius: 10px;
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .modal-header {
        padding: 20px;
        border-bottom: 1px solid #2a3a5a;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-header h2 {
        margin: 0;
        color: #e6f0ff;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .close-modal {
        background: none;
        border: none;
        color: #a0b3d6;
        font-size: 1.5rem;
        cursor: pointer;
    }
    
    .modal-body {
        padding: 20px;
    }
    
    /* Formulários */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #dceaff;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px;
        background: rgba(255, 255, 255, 0.05);
        border: 2px solid #24344d;
        border-radius: 8px;
        color: #e6f0ff;
        font-size: 1rem;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #4da3ff;
        box-shadow: 0 0 0 3px rgba(77, 163, 255, 0.2);
    }
    
    .form-group.checkbox {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .form-group.checkbox input {
        width: auto;
    }
    
    .form-footer {
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #2a3a5a;
    }
    
    .btn {
        padding: 12px 25px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-size: 0.95rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
        text-decoration: none;
    }
    
    .btn.cancelar {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        color: white;
    }
    
    .btn.salvar {
        background: linear-gradient(135deg, #00cc66 0%, #00994d 100%);
        color: white;
    }
    
    .btn.excluir {
        background: linear-gradient(135deg, #ff4d4d 0%, #cc0000 100%);
        color: white;
    }
    
    /* Toast */
    .toast {
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 8px;
        color: white;
        z-index: 1001;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.3s;
    }
    
    .toast.show {
        opacity: 1;
        transform: translateY(0);
    }
    
    .toast.success {
        background: linear-gradient(135deg, #00cc66 0%, #00994d 100%);
    }
    
    .toast.error {
        background: linear-gradient(135deg, #ff4d4d 0%, #cc0000 100%);
    }
    
    .toast.info {
        background: linear-gradient(135deg, #4da3ff 0%, #2d8cff 100%);
    }
    
    /* Aviso IP */
    .aviso-ip {
        background: rgba(77, 163, 255, 0.1);
        border: 1px solid #4da3ff;
        border-radius: 8px;
        padding: 15px;
        margin: 20px 0;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .aviso-ip i {
        color: #4da3ff;
        font-size: 1.5rem;
    }
    
    .aviso-ip p {
        margin: 0;
        color: #a0d2ff;
        line-height: 1.5;
    }
    
    /* Modal de confirmação */
    .confirm-modal {
        max-width: 500px;
    }
    
    .confirm-modal .modal-body {
        text-align: center;
    }
    
    .confirm-modal p {
        font-size: 1.1rem;
        margin-bottom: 25px;
        color: #e6f0ff;
    }
    
    .modal-small {
        max-width: 400px;
    }
    </style>
</head>
<body>

<div class="container">
    <h1><i class="fas fa-user-shield"></i> Gerenciar Administradores</h1>
    
    <div class="painel-acoes">
        <a href="admin.php" class="btn voltar">
            <i class="fas fa-arrow-left"></i> Voltar ao Painel
        </a>
        <button class="btn novo-admin" onclick="abrirModalNovoAdmin()">
            <i class="fas fa-user-plus"></i> Novo Administrador
        </button>
        <a href="logout.php" class="btn logout">
            <i class="fas fa-sign-out-alt"></i> Sair
        </a>
    </div>
    
    <div class="aviso-ip">
        <i class="fas fa-info-circle"></i>
        <p>
            <strong>Sistema de Segurança por IP:</strong> Os administradores só poderão acessar o painel se 
            estiverem conectados a partir dos IPs permitidos cadastrados. Para acesso de qualquer local, 
            deixe o campo "IP Permitido" em branco.
        </p>
    </div>
    
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon" style="color: #4da3ff;">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-number"><?= count($administradores) ?></div>
            <div class="stat-label">Total de Administradores</div>
        </div>
        
        <?php
        // Calcular estatísticas
        $ativos = array_filter($administradores, function($admin) {
            return $admin['ativo'] == 1 && $admin['bloqueado'] == 0;
        });
        
        $bloqueados = array_filter($administradores, function($admin) {
            return $admin['bloqueado'] == 1;
        });
        
        $super_admins = array_filter($administradores, function($admin) {
            return $admin['nivel_acesso'] == 'super';
        });
        ?>
        
        <div class="stat-card">
            <div class="stat-icon" style="color: #00cc66;">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-number"><?= count($ativos) ?></div>
            <div class="stat-label">Ativos</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="color: #ff3333;">
                <i class="fas fa-user-lock"></i>
            </div>
            <div class="stat-number"><?= count($bloqueados) ?></div>
            <div class="stat-label">Bloqueados</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="color: #9b59b6;">
                <i class="fas fa-crown"></i>
            </div>
            <div class="stat-number"><?= count($super_admins) ?></div>
            <div class="stat-label">Super Administradores</div>
        </div>
    </div>
    
    <div class="table-container">
        <table class="tabela">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuário</th>
                    <th>Nome Completo</th>
                    <th>Email</th>
                    <th>IP Permitido</th>
                    <th>Status</th>
                    <th>Nível de Acesso</th>
                    <th>Criado em</th>
                    <th>Último Login</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($administradores as $admin): 
                    $status_class = '';
                    $status_text = '';
                    $status_icon = '';
                    
                    if ($admin['bloqueado'] == 1) {
                        $status_class = 'badge-bloqueado';
                        $status_text = 'Bloqueado';
                        $status_icon = 'fa-lock';
                    } elseif ($admin['ativo'] == 0) {
                        $status_class = 'badge-inativo';
                        $status_text = 'Inativo';
                        $status_icon = 'fa-user-slash';
                    } else {
                        $status_class = 'badge-ativo';
                        $status_text = 'Ativo';
                        $status_icon = 'fa-user-check';
                    }
                    
                    $nivel_class = $admin['nivel_acesso'] == 'super' ? 'badge-super' : 'badge-admin';
                    $nivel_text = $admin['nivel_acesso'] == 'super' ? 'Super Admin' : 'Administrador';
                ?>
                <tr>
                    <td><?= htmlspecialchars($admin['id']) ?></td>
                    <td><strong><?= htmlspecialchars($admin['usuario']) ?></strong></td>
                    <td><?= htmlspecialchars($admin['nome_completo']) ?></td>
                    <td><?= htmlspecialchars($admin['email']) ?></td>
                    <td>
                        <?php if ($admin['ip_permitido']): ?>
                        <code style="background: #1b263b; padding: 3px 8px; border-radius: 4px;">
                            <?= htmlspecialchars($admin['ip_permitido']) ?>
                        </code>
                        <?php else: ?>
                        <span style="color: #7a8ca5;">Qualquer IP</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge-status <?= $status_class ?>">
                            <i class="fas <?= $status_icon ?>"></i> <?= $status_text ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge-status <?= $nivel_class ?>">
                            <i class="fas <?= $admin['nivel_acesso'] == 'super' ? 'fa-crown' : 'fa-user-tie' ?>"></i> 
                            <?= $nivel_text ?>
                        </span>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($admin['criado_em'])) ?></td>
                    <td>
                        <?php if ($admin['ultimo_login']): ?>
                        <?= date('d/m/Y H:i', strtotime($admin['ultimo_login'])) ?>
                        <?php else: ?>
                        <span style="color: #7a8ca5;">Nunca acessou</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="acoes">
                            <button class="btn-acao editar" onclick="abrirModalEditar(<?= $admin['id'] ?>)">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn-acao senha" onclick="abrirModalSenha(<?= $admin['id'] ?>, '<?= htmlspecialchars(addslashes($admin['usuario'])) ?>')">
                                <i class="fas fa-key"></i> Senha
                            </button>
                            <?php if ($admin['bloqueado'] == 1): ?>
                            <button class="btn-acao desbloquear" onclick="desbloquearAdmin(<?= $admin['id'] ?>)">
                                <i class="fas fa-unlock"></i> Desbloquear
                            </button>
                            <?php else: ?>
                            <button class="btn-acao bloquear" onclick="bloquearAdmin(<?= $admin['id'] ?>, '<?= htmlspecialchars(addslashes($admin['usuario'])) ?>')">
                                <i class="fas fa-lock"></i> Bloquear
                            </button>
                            <?php endif; ?>
                            <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                            <button class="btn-acao excluir" onclick="excluirAdmin(<?= $admin['id'] ?>, '<?= htmlspecialchars(addslashes($admin['usuario'])) ?>')">
                                <i class="fas fa-trash"></i> Excluir
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (empty($administradores)): ?>
        <div style="text-align: center; padding: 40px; color: #a0b3d6;">
            <i class="fas fa-users-slash" style="font-size: 3rem; margin-bottom: 15px;"></i>
            <p>Nenhum administrador encontrado</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Novo Administrador -->
<div id="modalNovoAdmin" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-user-plus"></i> Novo Administrador</h2>
            <button class="close-modal" onclick="fecharModalNovoAdmin()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formNovoAdmin">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="novo_usuario"><i class="fas fa-user"></i> Usuário *</label>
                        <input type="text" id="novo_usuario" name="usuario" required 
                               placeholder="ex: admin.joao">
                    </div>
                    
                    <div class="form-group">
                        <label for="novo_nome_completo"><i class="fas fa-id-card"></i> Nome Completo *</label>
                        <input type="text" id="novo_nome_completo" name="nome_completo" required 
                               placeholder="ex: João Silva">
                    </div>
                    
                    <div class="form-group">
                        <label for="novo_email"><i class="fas fa-envelope"></i> Email *</label>
                        <input type="email" id="novo_email" name="email" required 
                               placeholder="ex: joao@empresa.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="novo_senha"><i class="fas fa-lock"></i> Senha *</label>
                        <input type="password" id="novo_senha" name="senha" required 
                               placeholder="Mínimo 6 caracteres">
                    </div>
                    
                    <div class="form-group">
                        <label for="novo_confirmar_senha"><i class="fas fa-lock"></i> Confirmar Senha *</label>
                        <input type="password" id="novo_confirmar_senha" name="confirmar_senha" required 
                               placeholder="Repita a senha">
                    </div>
                    
                    <div class="form-group">
                        <label for="novo_ip_permitido"><i class="fas fa-network-wired"></i> IP Permitido</label>
                        <input type="text" id="novo_ip_permitido" name="ip_permitido" 
                               placeholder="ex: 192.168.1.100 ou 192.168.1.*">
                        <small style="color: #7a8ca5; display: block; margin-top: 5px;">
                            Deixe em branco para permitir qualquer IP
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="novo_nivel_acesso"><i class="fas fa-user-tag"></i> Nível de Acesso</label>
                        <select id="novo_nivel_acesso" name="nivel_acesso">
                            <option value="admin">Administrador</option>
                            <option value="super">Super Administrador</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-footer">
                    <button type="button" class="btn cancelar" onclick="fecharModalNovoAdmin()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn salvar">
                        <i class="fas fa-save"></i> Criar Administrador
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Administrador -->
<div id="modalEditarAdmin" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-user-edit"></i> Editar Administrador</h2>
            <button class="close-modal" onclick="fecharModalEditar()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formEditarAdmin">
                <input type="hidden" id="editar_id" name="id">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="editar_usuario"><i class="fas fa-user"></i> Usuário *</label>
                        <input type="text" id="editar_usuario" name="usuario" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_nome_completo"><i class="fas fa-id-card"></i> Nome Completo *</label>
                        <input type="text" id="editar_nome_completo" name="nome_completo" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_email"><i class="fas fa-envelope"></i> Email *</label>
                        <input type="email" id="editar_email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_ip_permitido"><i class="fas fa-network-wired"></i> IP Permitido</label>
                        <input type="text" id="editar_ip_permitido" name="ip_permitido">
                        <small style="color: #7a8ca5; display: block; margin-top: 5px;">
                            Use * para faixa de IP (ex: 192.168.1.*)
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_nivel_acesso"><i class="fas fa-user-tag"></i> Nível de Acesso</label>
                        <select id="editar_nivel_acesso" name="nivel_acesso">
                            <option value="admin">Administrador</option>
                            <option value="super">Super Administrador</option>
                        </select>
                    </div>
                    
                    <div class="form-group checkbox">
                        <input type="checkbox" id="editar_ativo" name="ativo">
                        <label for="editar_ativo" style="margin: 0;">
                            <i class="fas fa-check-circle"></i> Usuário ativo
                        </label>
                    </div>
                </div>
                
                <div class="form-footer">
                    <button type="button" class="btn cancelar" onclick="fecharModalEditar()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn salvar">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Alterar Senha -->
<div id="modalSenha" class="modal">
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h2><i class="fas fa-key"></i> Alterar Senha</h2>
            <button class="close-modal" onclick="fecharModalSenha()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formAlterarSenha">
                <input type="hidden" id="senha_id" name="id">
                <input type="hidden" id="senha_usuario" name="usuario">
                
                <div class="form-group">
                    <label for="nova_senha"><i class="fas fa-lock"></i> Nova Senha *</label>
                    <input type="password" id="nova_senha" name="nova_senha" required 
                           placeholder="Mínimo 6 caracteres">
                </div>
                
                <div class="form-group">
                    <label for="confirmar_senha"><i class="fas fa-lock"></i> Confirmar Nova Senha *</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required 
                           placeholder="Repita a senha">
                </div>
                
                <div class="form-footer">
                    <button type="button" class="btn cancelar" onclick="fecharModalSenha()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn salvar">
                        <i class="fas fa-save"></i> Alterar Senha
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Confirmação -->
<div id="modalConfirmacao" class="modal">
    <div class="modal-content confirm-modal">
        <div class="modal-header">
            <h2><i class="fas fa-exclamation-triangle"></i> Confirmação</h2>
            <button class="close-modal" onclick="fecharModalConfirmacao()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="confirmacaoMensagem"></p>
            <div class="form-footer">
                <button type="button" class="btn cancelar" onclick="fecharModalConfirmacao()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn excluir" id="btnConfirmarAcao">
                    <i class="fas fa-check"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast de Notificação -->
<div id="toast" class="toast"></div>

<script>
// Variáveis globais
let adminIdParaAcao = null;
let acaoConfirmacao = null;
let adminInfoParaAcao = null;

// Funções dos modais
function abrirModalNovoAdmin() {
    document.getElementById('modalNovoAdmin').style.display = 'flex';
    document.getElementById('formNovoAdmin').reset();
}

function fecharModalNovoAdmin() {
    document.getElementById('modalNovoAdmin').style.display = 'none';
}

function abrirModalEditar(id) {
    adminIdParaAcao = id;
    
    fetch('administradores.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_admin&id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const admin = data.admin;
            document.getElementById('editar_id').value = admin.id;
            document.getElementById('editar_usuario').value = admin.usuario;
            document.getElementById('editar_nome_completo').value = admin.nome_completo;
            document.getElementById('editar_email').value = admin.email;
            document.getElementById('editar_ip_permitido').value = admin.ip_permitido || '';
            document.getElementById('editar_nivel_acesso').value = admin.nivel_acesso;
            document.getElementById('editar_ativo').checked = admin.ativo == 1;
            
            document.getElementById('modalEditarAdmin').style.display = 'flex';
        } else {
            mostrarToast(data.message, 'error');
        }
    })
    .catch(error => {
        mostrarToast('Erro ao carregar dados', 'error');
        console.error('Error:', error);
    });
}

function fecharModalEditar() {
    document.getElementById('modalEditarAdmin').style.display = 'none';
    adminIdParaAcao = null;
}

function abrirModalSenha(id, usuario) {
    adminIdParaAcao = id;
    document.getElementById('senha_id').value = id;
    document.getElementById('senha_usuario').value = usuario;
    document.getElementById('formAlterarSenha').reset();
    document.getElementById('modalSenha').style.display = 'flex';
}

function fecharModalSenha() {
    document.getElementById('modalSenha').style.display = 'none';
    adminIdParaAcao = null;
}

// Funções de ação
function bloquearAdmin(id, usuario) {
    adminIdParaAcao = id;
    acaoConfirmacao = 'bloquear';
    adminInfoParaAcao = usuario;
    
    document.getElementById('confirmacaoMensagem').innerHTML = 
        `Deseja bloquear o administrador <strong>"${usuario}"</strong>?<br><br>
         <small>Ele não poderá acessar o painel até ser desbloqueado.</small>`;
    
    document.getElementById('btnConfirmarAcao').innerHTML = '<i class="fas fa-lock"></i> Bloquear';
    document.getElementById('modalConfirmacao').style.display = 'flex';
}

function desbloquearAdmin(id) {
    adminIdParaAcao = id;
    acaoConfirmacao = 'desbloquear';
    
    fetch('administradores.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=toggle_block&id=' + id + '&bloquear=0'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarToast(data.message, 'success');
            if (data.reload) {
                setTimeout(() => location.reload(), 1500);
            }
        } else {
            mostrarToast(data.message, 'error');
        }
    });
}

function excluirAdmin(id, usuario) {
    adminIdParaAcao = id;
    acaoConfirmacao = 'excluir';
    adminInfoParaAcao = usuario;
    
    document.getElementById('confirmacaoMensagem').innerHTML = 
        `Tem certeza que deseja excluir o administrador <strong>"${usuario}"</strong>?<br><br>
         <small>Esta ação não pode ser desfeita e todos os dados serão perdidos.</small>`;
    
    document.getElementById('btnConfirmarAcao').innerHTML = '<i class="fas fa-trash"></i> Excluir';
    document.getElementById('modalConfirmacao').style.display = 'flex';
}

function fecharModalConfirmacao() {
    document.getElementById('modalConfirmacao').style.display = 'none';
    adminIdParaAcao = null;
    acaoConfirmacao = null;
    adminInfoParaAcao = null;
}

// Confirmar ação
document.getElementById('btnConfirmarAcao').addEventListener('click', function() {
    if (!adminIdParaAcao || !acaoConfirmacao) return;
    
    let urlParams = '';
    
    if (acaoConfirmacao === 'bloquear') {
        urlParams = 'action=toggle_block&id=' + adminIdParaAcao + '&bloquear=1';
    } else if (acaoConfirmacao === 'excluir') {
        urlParams = 'action=delete_admin&id=' + adminIdParaAcao;
    }
    
    if (urlParams) {
        fetch('administradores.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: urlParams
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarToast(data.message, 'success');
                if (data.reload) {
                    setTimeout(() => location.reload(), 1500);
                }
            } else {
                mostrarToast(data.message, 'error');
            }
            fecharModalConfirmacao();
        })
        .catch(error => {
            mostrarToast('Erro ao processar ação', 'error');
            console.error('Error:', error);
            fecharModalConfirmacao();
        });
    }
});

// Formulários
document.getElementById('formNovoAdmin').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'add_admin');
    
    fetch('administradores.php', {
        method: 'POST',
        body: new URLSearchParams(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarToast(data.message, 'success');
            fecharModalNovoAdmin();
            if (data.reload) {
                setTimeout(() => location.reload(), 1500);
            }
        } else {
            mostrarToast(data.message, 'error');
        }
    })
    .catch(error => {
        mostrarToast('Erro ao criar administrador', 'error');
        console.error('Error:', error);
    });
});

document.getElementById('formEditarAdmin').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'edit_admin');
    
    fetch('administradores.php', {
        method: 'POST',
        body: new URLSearchParams(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarToast(data.message, 'success');
            fecharModalEditar();
            if (data.reload) {
                setTimeout(() => location.reload(), 1500);
            }
        } else {
            mostrarToast(data.message, 'error');
        }
    })
    .catch(error => {
        mostrarToast('Erro ao atualizar administrador', 'error');
        console.error('Error:', error);
    });
});

document.getElementById('formAlterarSenha').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'update_password');
    
    fetch('administradores.php', {
        method: 'POST',
        body: new URLSearchParams(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarToast(data.message, 'success');
            fecharModalSenha();
        } else {
            mostrarToast(data.message, 'error');
        }
    })
    .catch(error => {
        mostrarToast('Erro ao alterar senha', 'error');
        console.error('Error:', error);
    });
});

// Toast notification
function mostrarToast(mensagem, tipo = 'info') {
    const toast = document.getElementById('toast');
    toast.textContent = mensagem;
    toast.className = 'toast show ' + tipo;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Fechar modais ao clicar fora
window.onclick = function(event) {
    const modais = ['modalNovoAdmin', 'modalEditarAdmin', 'modalSenha', 'modalConfirmacao'];
    
    modais.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target === modal) {
            if (modalId === 'modalNovoAdmin') fecharModalNovoAdmin();
            if (modalId === 'modalEditarAdmin') fecharModalEditar();
            if (modalId === 'modalSenha') fecharModalSenha();
            if (modalId === 'modalConfirmacao') fecharModalConfirmacao();
        }
    });
}

// Fechar com ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        fecharModalNovoAdmin();
        fecharModalEditar();
        fecharModalSenha();
        fecharModalConfirmacao();
    }
});

// Auto-detect IP para facilitar o cadastro
window.addEventListener('load', function() {
    // Obter IP atual do usuário
    fetch('https://api.ipify.org?format=json')
    .then(response => response.json())
    .then(data => {
        // Adicionar o IP atual como placeholder nos campos de IP
        const camposIP = document.querySelectorAll('input[name="ip_permitido"]');
        camposIP.forEach(campo => {
            campo.placeholder = `Seu IP atual: ${data.ip}`;
        });
        
        // Botão para preencher automaticamente
        const novoCampoIP = document.getElementById('novo_ip_permitido');
        if (novoCampoIP) {
            const container = novoCampoIP.parentElement;
            
            const btnAutoIP = document.createElement('button');
            btnAutoIP.type = 'button';
            btnAutoIP.className = 'btn-acao editar';
            btnAutoIP.innerHTML = '<i class="fas fa-magic"></i> Usar meu IP';
            btnAutoIP.style.marginTop = '5px';
            btnAutoIP.onclick = function() {
                novoCampoIP.value = data.ip;
            };
            
            container.appendChild(btnAutoIP);
        }
    })
    .catch(error => {
        console.log('Não foi possível obter o IP:', error);
    });
});
</script>

</body>
</html>
