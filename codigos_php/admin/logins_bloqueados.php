<?php
session_start();
require_once '../conexao.php';

// Impedir acesso sem login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Buscar logins bloqueados
$sql = "SELECT id, ip, navegador, sistema_operacional, bloked_until, reason, created_at
        FROM logins_bloqueados 
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$logins_bloqueados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar exclusão via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
        if ($id) {
            try {
                $deleteSql = "DELETE FROM logins_bloqueados WHERE id = :id";
                $deleteStmt = $conn->prepare($deleteSql);
                $deleteStmt->bindParam(':id', $id, PDO::PARAM_INT);
                $deleteStmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Registro excluído com sucesso!']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Erro ao excluir registro: ' . $e->getMessage()]);
            }
        }
        exit;
    }
    
    // Limpar todos os registros
    if ($_POST['action'] === 'clear_all') {
        try {
            $clearSql = "DELETE FROM logins_bloqueados";
            $clearStmt = $conn->prepare($clearSql);
            $clearStmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Todos os registros foram limpos com sucesso!']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao limpar registros: ' . $e->getMessage()]);
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
    <title>Logins Bloqueados</title>
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
        
        /* Badge para motivo de bloqueio */
        .badge-bloqueio {
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
        
        .badge-senha-incorreta {
            background-color: #FF9800;
            color: white;
        }
        
        .badge-tentativas-excedidas {
            background-color: #F44336;
            color: white;
        }
        
        .badge-ip-bloqueado {
            background-color: #9C27B0;
            color: white;
        }
        
        .badge-outro {
            background-color: #607D8B;
            color: white;
        }

        /* Ajuste para a nova coluna */
        .tabela th:nth-child(10), 
        .tabela td:nth-child(10) {
            width: 120px;
            text-align: center;
        }
        
        /* Ajuste para número de colunas */
        .tabela th:nth-child(11), 
        .tabela td:nth-child(11) {
            width: 150px;
            text-align: center;
        }
        
        /* Botão limpar tudo */
        .btn-limpar-tudo {
            background: linear-gradient(135deg, #FF5722, #E64A19);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-limpar-tudo:hover {
            background: linear-gradient(135deg, #E64A19, #D84315);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 87, 34, 0.3);
        }
        
        /* Contador de registros */
        .contador-registros {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px 15px;
            margin-bottom: 15px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: bold;
            color: #495057;
        }
        
        .contador-registros .numero {
            background-color: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1><i class="fas fa-ban"></i> Logins Bloqueados</h1>

    <div class="painel-acoes">
        <a href="usuarios.php" class="btn voltar">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <button class="btn-limpar-tudo" onclick="clearAll()">
            <i class="fas fa-broom"></i> Limpar Todos
        </button>
        <a href="logout.php" class="btn logout">
            <i class="fas fa-sign-out-alt"></i> Sair
        </a>
    </div>
    
    <!-- Contador de registros -->
    <div class="contador-registros">
        <i class="fas fa-database"></i>
        Total de Registros: 
        <span class="numero"><?= count($logins_bloqueados) ?></span>
    </div>

    <div class="table-container">
        <table class="tabela">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>IP</th>
                    <th>Navegador</th>
                    <th>Sistema</th>
                    <th>Data/Hora</th>
                    <th>Tipo de Login</th>
                    <th>Motivo Bloqueio</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logins_bloqueados)): ?>
                    <?php foreach ($logins_bloqueados as $linha): 
                        $provider = $linha['provider'] ?? 'local';
                        $badgeClass = ($provider == 'google') ? 'badge-google' : 
                                     (($provider == 'local') ? 'badge-local' : 'badge-unknown');
                        $providerIcon = ($provider == 'google') ? 'fab fa-google' : 'fas fa-user';
                        $providerText = ($provider == 'google') ? 'Google' : 
                                       (($provider == 'local') ? 'Local' : ucfirst($provider));
                        
                        // Determinar badge do motivo de bloqueio
                        $motivo = $linha['motivo_bloqueio'] ?? 'outro';
                        $motivoClass = '';
                        $motivoIcon = '';
                        $motivoText = '';
                        
                        switch(strtolower($motivo)) {
                            case 'senha_incorreta':
                                $motivoClass = 'badge-senha-incorreta';
                                $motivoIcon = 'fas fa-key';
                                $motivoText = 'Senha Incorreta';
                                break;
                            case 'tentativas_excedidas':
                                $motivoClass = 'badge-tentativas-excedidas';
                                $motivoIcon = 'fas fa-exclamation-circle';
                                $motivoText = 'Tentativas Excedidas';
                                break;
                            case 'ip_bloqueado':
                                $motivoClass = 'badge-ip-bloqueado';
                                $motivoIcon = 'fas fa-network-wired';
                                $motivoText = 'IP Bloqueado';
                                break;
                            default:
                                $motivoClass = 'badge-outro';
                                $motivoIcon = 'fas fa-question-circle';
                                $motivoText = ucfirst(str_replace('_', ' ', $motivo));
                        }
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
                            $tentativaTime = new DateTime($linha['tentativa_time']);
                            echo $tentativaTime->format('d/m/Y H:i:s');
                            ?>
                        </td>
                        <td>
                            <span class="badge <?= $badgeClass ?>">
                                <i class="<?= $providerIcon ?>"></i>
                                <?= $providerText ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge-bloqueio <?= $motivoClass ?>">
                                <i class="<?= $motivoIcon ?>"></i>
                                <?= $motivoText ?>
                            </span>
                        </td>
                        <td class="acoes">
                            <button class="btn excluir" onclick="deleteRecord(<?= $linha['id'] ?>, '<?= htmlspecialchars(addslashes($linha['nome_completo'])) ?>')">
                                <i class="fas fa-trash"></i> Excluir
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="12" style="text-align:center; padding: 20px;">
                            <i class="fas fa-check-circle" style="font-size: 48px; color: #4CAF50; margin-bottom: 10px;"></i><br>
                            Nenhum login bloqueado registrado
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
            <p id="confirmMessage">Tem certeza que deseja excluir este registro?</p>
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

<!-- Modal de Confirmação Limpar Tudo -->
<div id="clearAllModal" class="modal">
    <div class="modal-content confirm-modal">
        <div class="modal-header">
            <h2><i class="fas fa-exclamation-triangle"></i> Limpar Todos os Registros</h2>
            <button class="close-modal" onclick="closeClearAllModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="clearAllMessage">
                <i class="fas fa-exclamation-circle" style="color: #FF9800; font-size: 24px; margin-right: 10px;"></i>
                Atenção! Esta ação removerá <strong>TODOS</strong> os registros de logins bloqueados.<br><br>
                <strong>Total de registros: <?= count($logins_bloqueados) ?></strong><br><br>
                Esta ação não pode ser desfeita.
            </p>
            <div class="form-footer">
                <button type="button" class="btn cancelar" onclick="closeClearAllModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn excluir-confirm" onclick="confirmClearAll()">
                    <i class="fas fa-broom"></i> Limpar Tudo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast de Notificação -->
<div id="toast" class="toast"></div>

<script>
let currentRecordId = null;

// Modal de Confirmação para registro individual
function deleteRecord(recordId, userName) {
    currentRecordId = recordId;
    document.getElementById('confirmMessage').innerHTML = 
        `Tem certeza que deseja excluir o registro do usuário <strong>"${userName}"</strong>?`;
    document.getElementById('confirmModal').style.display = 'flex';
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
    currentRecordId = null;
}

function confirmDelete() {
    if (!currentRecordId) return;
    
    fetch('logins_bloqueados.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=delete&id=' + currentRecordId
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
        showToast('Erro ao excluir registro', 'error');
        console.error('Error:', error);
        closeConfirmModal();
    });
}

// Modal Limpar Todos os Registros
function clearAll() {
    <?php if (empty($logins_bloqueados)): ?>
        showToast('Não há registros para limpar.', 'info');
        return;
    <?php endif; ?>
    
    document.getElementById('clearAllModal').style.display = 'flex';
}

function closeClearAllModal() {
    document.getElementById('clearAllModal').style.display = 'none';
}

function confirmClearAll() {
    fetch('logins_bloqueados.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=clear_all'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
        closeClearAllModal();
    })
    .catch(error => {
        showToast('Erro ao limpar registros', 'error');
        console.error('Error:', error);
        closeClearAllModal();
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

// Fechar modais ao clicar fora
window.onclick = function(event) {
    const confirmModal = document.getElementById('confirmModal');
    const clearAllModal = document.getElementById('clearAllModal');
    
    if (event.target === confirmModal) {
        closeConfirmModal();
    }
    if (event.target === clearAllModal) {
        closeClearAllModal();
    }
}

// Fechar com ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeConfirmModal();
        closeClearAllModal();
    }
});
</script>

</body>
</html>
