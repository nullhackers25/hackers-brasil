<?php
session_start();
require_once '../conexao.php';

// Impedir acesso sem login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit;
}

// Buscar usuários da tabela usuarios_online - AGORA INCLUINDO SESSION_ID
$sql = "SELECT id, user_id, nome_completo, email, usuario, ip, navegador, sistema_operacional, 
               login_time, last_activity, session_id, status, provider
        FROM usuarios_online 
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar exclusão via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
        if ($id) {
            try {
                $deleteSql = "DELETE FROM usuarios_online WHERE id = :id";
                $deleteStmt = $conn->prepare($deleteSql);
                $deleteStmt->bindParam(':id', $id, PDO::PARAM_INT);
                $deleteStmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Usuário excluído com sucesso!']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Erro ao excluir usuário: ' . $e->getMessage()]);
            }
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários Online</title>
    <link rel="stylesheet" href="usuarios_bank.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos para os badges de provider */
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            min-width: 80px;
        }

        .badge-google {
            background-color: #4285F4;
            color: white;
        }

        .badge-local {
            background-color: #34A853;
            color: white;
        }

        .badge-unknown {
            background-color: #9E9E9E;
            color: white;
        }

        /* Ajuste para as novas colunas */
        .tabela th:nth-child(10), 
        .tabela td:nth-child(10) {
            width: 120px;
            text-align: center;
        }
        
        .tabela th:nth-child(11), 
        .tabela td:nth-child(11) {
            width: 150px;
            text-align: center;
        }
        
        .tabela th:nth-child(12), 
        .tabela td:nth-child(12) {
            width: 100px;
            text-align: center;
        }
        
        .tabela th:nth-child(13), 
        .tabela td:nth-child(13) {
            width: 120px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container">
    <h1><i class="fas fa-users"></i> Usuários Online</h1>

    <div class="painel-acoes">
        <a href="usuarios.php" class="btn voltar">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <a href="logout.php" class="btn logout">
            <i class="fas fa-sign-out-alt"></i> Sair
        </a>
    </div>

    <div class="table-container">
        <table class="tabela">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User ID</th>
                    <th>Nome Completo</th>
                    <th>Email</th>
                    <th>Usuário</th>
                    <th>IP</th>
                    <th>Navegador</th>
                    <th>Sistema</th>
                    <th>Login</th>
                    <th>Última Atividade</th>
                    <th>Session ID</th>
                    <th>Status</th>
                    <th>Tipo de Login</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($usuarios)): ?>
                    <?php foreach ($usuarios as $linha): 
                        $provider = $linha['provider'] ?? 'local';
                        $badgeClass = ($provider == 'google') ? 'badge-google' : 
                                     (($provider == 'local') ? 'badge-local' : 'badge-unknown');
                        $providerIcon = ($provider == 'google') ? 'fab fa-google' : 'fas fa-user';
                        $providerText = ($provider == 'google') ? 'Google' : 
                                       (($provider == 'local') ? 'Local' : ucfirst($provider));
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($linha['id']) ?></td>
                        <td><?= htmlspecialchars($linha['user_id']) ?></td>
                        <td><?= htmlspecialchars($linha['nome_completo']) ?></td>
                        <td><?= htmlspecialchars($linha['email']) ?></td>
                        <td><?= htmlspecialchars($linha['usuario']) ?></td>
                        <td><?= htmlspecialchars($linha['ip']) ?></td>
                        <td title="<?= htmlspecialchars($linha['navegador']) ?>">
                            <?= strlen($linha['navegador']) > 30 ? htmlspecialchars(substr($linha['navegador'], 0, 30)) . '...' : htmlspecialchars($linha['navegador']) ?>
                        </td>
                        <td><?= htmlspecialchars($linha['sistema_operacional']) ?></td>
                        <td>
                            <?php 
                            $loginTime = new DateTime($linha['login_time']);
                            echo $loginTime->format('d/m/Y H:i:s');
                            ?>
                        </td>
                        <td>
                            <?php 
                            $lastActivity = new DateTime($linha['last_activity']);
                            echo $lastActivity->format('d/m/Y H:i:s');
                            ?>
                        </td>
                        <td>
                            <span title="<?= htmlspecialchars($linha['session_id'] ?? '') ?>">
                                <?= $linha['session_id'] ? htmlspecialchars(substr($linha['session_id'], 0, 8)) . '...' : '-' ?>
                            </span>
                        </td>
                        <td>
                            <span style="color: <?= ($linha['status'] ?? 'online') == 'online' ? 'green' : 'red' ?>">
                                <i class="fas fa-circle"></i> <?= htmlspecialchars($linha['status'] ?? 'online') ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $badgeClass ?>">
                                <i class="<?= $providerIcon ?>"></i>
                                <?= $providerText ?>
                            </span>
                        </td>
                        <td class="acoes">
                            <button class="btn excluir" onclick="deleteUser(<?= $linha['id'] ?>, '<?= htmlspecialchars(addslashes($linha['nome_completo'])) ?>')">
                                <i class="fas fa-trash"></i> Excluir
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="14" style="text-align:center; padding: 20px;">
                            <i class="fas fa-users-slash" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i><br>
                            Nenhum usuário online no momento
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div id="confirmModal" class="modal">
    <div class="modal-content confirm-modal">
        <div class="modal-header">
            <h2><i class="fas fa-exclamation-triangle"></i> Confirmar Exclusão</h2>
            <button class="close-modal" onclick="closeConfirmModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="confirmMessage">Tem certeza que deseja excluir este usuário?</p>
            <div class="form-footer">
                <button type="button" class="btn cancelar" onclick="closeConfirmModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn excluir-confirm" onclick="confirmDelete()">
                    <i class="fas fa-trash"></i> Sim, Excluir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast de Notificação -->
<div id="toast" class="toast"></div>

<script>
let currentUserId = null;

// Modal de Confirmação
function deleteUser(userId, userName) {
    currentUserId = userId;
    document.getElementById('confirmMessage').innerHTML = 
        `Tem certeza que deseja <strong>desconectar</strong> o usuário <strong>"${userName}"</strong>?<br>
         <small>Esta ação removerá o usuário da lista de online.</small>`;
    document.getElementById('confirmModal').style.display = 'flex';
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
    currentUserId = null;
}

function confirmDelete() {
    if (!currentUserId) return;
    
    fetch('usuarios_online.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=delete&id=' + currentUserId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
        closeConfirmModal();
    })
    .catch(error => {
        showToast('Erro ao desconectar usuário', 'error');
        console.error('Error:', error);
        closeConfirmModal();
    });
}

// Toast Notification
function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = 'toast show ' + type;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const confirmModal = document.getElementById('confirmModal');
    if (event.target === confirmModal) {
        closeConfirmModal();
    }
}

// Fechar com ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeConfirmModal();
    }
});
</script>

<script>
// Atualizar a página automaticamente a cada 1 minuto
setInterval(function() {
    location.reload();
}, 60000); // 60000ms = 1 minuto
</script>

</body>
</html>
