<?php
session_start();
require_once '../conexao.php';

// Impedir acesso sem login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Buscar usuários da tabela
$sql = "SELECT id, nome_completo, email, usuario, senha_hash, ip_cadastro, navegador, sistema_operacional, criado_em, provider, google_id, bloqueado_ate 
        FROM usuarios 
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
                $deleteSql = "DELETE FROM usuarios WHERE id = :id";
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
    
    if ($_POST['action'] === 'edit' && isset($_POST['id'])) {
        $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
        if ($id) {
            $editSql = "SELECT id, nome_completo, email, usuario FROM usuarios WHERE id = :id";
            $editStmt = $conn->prepare($editSql);
            $editStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $editStmt->execute();
            $usuario = $editStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario) {
                echo json_encode(['success' => true, 'usuario' => $usuario]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
            }
        }
        exit;
    }
    
    if ($_POST['action'] === 'update' && isset($_POST['id'])) {
        $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
        $nome = filter_var($_POST['nome'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $usuario = filter_var($_POST['usuario'], FILTER_SANITIZE_STRING);
        
        if ($id && $nome && $email && $usuario) {
            try {
                $updateSql = "UPDATE usuarios SET 
                              nome_completo = :nome, 
                              email = :email, 
                              usuario = :usuario 
                              WHERE id = :id";
                
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bindParam(':nome', $nome);
                $updateStmt->bindParam(':email', $email);
                $updateStmt->bindParam(':usuario', $usuario);
                $updateStmt->bindParam(':id', $id, PDO::PARAM_INT);
                $updateStmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Usuário atualizado com sucesso!']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar usuário: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
        }
        exit;
    }
    
    // AÇÃO DE BLOQUEAR USUÁRIO
    if ($_POST['action'] === 'bloquear' && isset($_POST['id'])) {
        $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
        $tipo = $_POST['tipo'];
        $quantidade = isset($_POST['quantidade']) ? intval($_POST['quantidade']) : 0;
        $motivo = $_POST['motivo'] ?? '';
        
        // Calcular data de expiração
        $bloqueado_ate = null;
        
        if ($tipo === 'permanente') {
            // Bloqueio permanente (2038 é o limite do timestamp UNIX 32-bit)
            $bloqueado_ate = '2038-01-01 00:00:00';
        } else {
            // Bloqueio temporário
            $now = time();
            
            switch($tipo) {
                case 'minutos':
                    $bloqueado_ate = date('Y-m-d H:i:s', $now + ($quantidade * 60));
                    break;
                case 'horas':
                    $bloqueado_ate = date('Y-m-d H:i:s', $now + ($quantidade * 3600));
                    break;
                case 'dias':
                    $bloqueado_ate = date('Y-m-d H:i:s', $now + ($quantidade * 86400));
                    break;
                case 'semanas':
                    $bloqueado_ate = date('Y-m-d H:i:s', $now + ($quantidade * 604800));
                    break;
                case 'meses':
                    // Aproximação de mês como 30 dias
                    $bloqueado_ate = date('Y-m-d H:i:s', $now + ($quantidade * 2592000));
                    break;
                default:
                    $bloqueado_ate = null;
            }
        }
        
        if ($id && $bloqueado_ate) {
            try {
                // Atualizar usuário
                $bloqueioSql = "UPDATE usuarios SET bloqueado_ate = :bloqueado_ate WHERE id = :id";
                $bloqueioStmt = $conn->prepare($bloqueioSql);
                $bloqueioStmt->bindParam(':bloqueado_ate', $bloqueado_ate);
                $bloqueioStmt->bindParam(':id', $id, PDO::PARAM_INT);
                $bloqueioStmt->execute();
                
                // Registrar no histórico
                $historicoSql = "INSERT INTO bloqueios_usuarios 
                                (usuario_id, administrador_id, motivo, bloqueado_ate, tipo) 
                                VALUES (:usuario_id, :admin_id, :motivo, :bloqueado_ate, 'manual')";
                $historicoStmt = $conn->prepare($historicoSql);
                $historicoStmt->bindParam(':usuario_id', $id, PDO::PARAM_INT);
                $historicoStmt->bindParam(':admin_id', $_SESSION['admin_id'], PDO::PARAM_INT);
                $historicoStmt->bindParam(':motivo', $motivo);
                $historicoStmt->bindParam(':bloqueado_ate', $bloqueado_ate);
                $historicoStmt->execute();
                
                // Mensagem personalizada
                if ($tipo === 'permanente') {
                    $mensagem = 'Usuário bloqueado permanentemente!';
                } else {
                    $mensagem = "Usuário bloqueado por $quantidade $tipo!";
                }
                
                echo json_encode(['success' => true, 'message' => $mensagem]);
                
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Erro ao bloquear usuário: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
        }
        exit;
    }
    
    // AÇÃO DE DESBLOQUEAR USUÁRIO
    if ($_POST['action'] === 'desbloquear' && isset($_POST['id'])) {
        $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
        
        if ($id) {
            try {
                $desbloqueioSql = "UPDATE usuarios SET bloqueado_ate = NULL WHERE id = :id";
                $desbloqueioStmt = $conn->prepare($desbloqueioSql);
                $desbloqueioStmt->bindParam(':id', $id, PDO::PARAM_INT);
                $desbloqueioStmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Usuário desbloqueado com sucesso!']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Erro ao desbloquear usuário: ' . $e->getMessage()]);
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
    <title>Gerenciar Usuários</title>
    <link rel="stylesheet" href="usuarios_bank.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="container">
    <h1>Usuários Ativos</h1>

    <div class="painel-acoes">
        <a href="usuarios.php" class="btn voltar">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <a href="editar_tabela_usuarios.php?tabela=usuarios" class="btn editar-tabela">
            <i class="fas fa-table"></i> Gerenciar Tabela
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
                    <th>Nome Completo</th>
                    <th>Email</th>
                    <th>Usuário</th>
                    <th>Senha Hash</th>
                    <th>IP Cadastro</th>
                    <th>Navegador</th>
                    <th>Sistema</th>
                    <th>Status</th>
                    <th>Criado em</th>
                    <th>Provider</th>
                    <th>Google ID</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($usuarios as $linha): 
                    // Verificar se usuário está bloqueado
                    $estaBloqueado = false;
                    $textoStatus = '';
                    if ($linha['bloqueado_ate']) {
                        $timestampBloqueio = strtotime($linha['bloqueado_ate']);
                        $timestampAtual = time();
                        if ($timestampBloqueio > $timestampAtual) {
                            $estaBloqueado = true;
                            $textoStatus = date('d/m/Y H:i', $timestampBloqueio);
                        }
                    }
                ?>
                <tr>
                    <td><?= htmlspecialchars($linha['id']) ?></td>
                    <td><?= htmlspecialchars($linha['nome_completo']) ?></td>
                    <td><?= htmlspecialchars($linha['email']) ?></td>
                    <td><?= htmlspecialchars($linha['usuario']) ?></td>
                    <td><?= htmlspecialchars($linha['senha_hash']) ?></td>
                    <td><?= htmlspecialchars($linha['ip_cadastro']) ?></td>
                    <td><?= htmlspecialchars($linha['navegador']) ?></td>
                    <td><?= htmlspecialchars($linha['sistema_operacional']) ?></td>
                    <td>
                        <?php if ($estaBloqueado): ?>
                            <span class="status-bloqueado" title="Bloqueado até <?= $textoStatus ?>">
                                <i class="fas fa-lock"></i> Bloqueado
                            </span>
                        <?php else: ?>
                            <span class="status-ativo">
                                <i class="fas fa-check"></i> Ativo
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($linha['criado_em'])) ?></td>
                    <td><?= htmlspecialchars($linha['provider']) ?></td>
                    <td><?= htmlspecialchars($linha['google_id']) ?></td>
                    <td class="acoes">
                        <button class="btn editar" onclick="openEditModal(<?= $linha['id'] ?>)">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        <?php if ($estaBloqueado): ?>
                            <button class="btn desbloquear" onclick="desbloquearUser(<?= $linha['id'] ?>, '<?= htmlspecialchars(addslashes($linha['nome_completo'])) ?>')">
                                <i class="fas fa-unlock"></i> Desbloquear
                            </button>
                        <?php else: ?>
                            <button class="btn bloquear" onclick="openBlockModal(<?= $linha['id'] ?>, '<?= htmlspecialchars(addslashes($linha['nome_completo'])) ?>')">
                                <i class="fas fa-lock"></i> Bloquear
                            </button>
                        <?php endif; ?>
                        <button class="btn excluir" onclick="deleteUser(<?= $linha['id'] ?>, '<?= htmlspecialchars(addslashes($linha['nome_completo'])) ?>')">
                            <i class="fas fa-trash"></i> Excluir
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (empty($usuarios)): ?>
    <div class="no-data">
        <i class="fas fa-users-slash"></i>
        <p>Nenhum usuário encontrado</p>
    </div>
    <?php endif; ?>
</div>

<!-- Modal de Edição -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-user-edit"></i> Editar Usuário</h2>
            <button class="close-modal" onclick="closeEditModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editUserForm">
                <input type="hidden" id="editUserId" name="id">
                
                <div class="form-group">
                    <label for="editNome"><i class="fas fa-user"></i> Nome Completo</label>
                    <input type="text" id="editNome" name="nome" required 
                           placeholder="Digite o nome completo">
                </div>
                
                <div class="form-group">
                    <label for="editEmail"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="editEmail" name="email" required 
                           placeholder="Digite o email">
                </div>
                
                <div class="form-group">
                    <label for="editUsuario"><i class="fas fa-user-circle"></i> Usuário</label>
                    <input type="text" id="editUsuario" name="usuario" required 
                           placeholder="Digite o nome de usuário">
                </div>
                
                <div class="form-footer">
                    <button type="button" class="btn cancelar" onclick="closeEditModal()">
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

<!-- Modal de Bloqueio -->
<div id="blockModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-lock"></i> Bloquear Usuário</h2>
            <button class="close-modal" onclick="closeBlockModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="blockUserForm">
                <input type="hidden" id="blockUserId" name="id">
                <input type="hidden" id="blockUserName" name="nome">
                
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Usuário:</label>
                    <p id="blockUserDisplay" style="padding: 10px; background: #1a2029; border-radius: 5px;"></p>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Tipo de Bloqueio:</label>
                    <div class="tempo-botoes">
                        <button type="button" class="tempo-btn" data-tipo="minutos" onclick="selecionarTempo('minutos')">
                            <i class="fas fa-hourglass-start"></i> Minutos
                        </button>
                        <button type="button" class="tempo-btn" data-tipo="horas" onclick="selecionarTempo('horas')">
                            <i class="fas fa-clock"></i> Horas
                        </button>
                        <button type="button" class="tempo-btn" data-tipo="dias" onclick="selecionarTempo('dias')">
                            <i class="fas fa-calendar-day"></i> Dias
                        </button>
                        <button type="button" class="tempo-btn" data-tipo="semanas" onclick="selecionarTempo('semanas')">
                            <i class="fas fa-calendar-week"></i> Semanas
                        </button>
                        <button type="button" class="tempo-btn" data-tipo="meses" onclick="selecionarTempo('meses')">
                            <i class="fas fa-calendar-alt"></i> Meses
                        </button>
                        <button type="button" class="tempo-btn permanente" data-tipo="permanente" onclick="selecionarTempo('permanente')">
                            <i class="fas fa-ban"></i> Permanente
                        </button>
                    </div>
                </div>
                
                <!-- Campo para digitar o número -->
                <div class="form-group" id="campoQuantidade" style="display: none;">
                    <label for="blockQuantidade"><i class="fas fa-hashtag"></i> Quantidade:</label>
                    <input type="number" id="blockQuantidade" name="quantidade" 
                           min="1" max="999" 
                           placeholder="Digite o número">
                    <small id="unidadeTempo">unidades</small>
                </div>
                
                <!-- Aviso para permanente -->
                <div class="form-group" id="avisoPermanente" style="display: none;">
                    <div class="aviso alerta">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p><strong>Atenção!</strong> O usuário será bloqueado permanentemente. Esta ação é irreversível.</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="blockMotivo"><i class="fas fa-comment"></i> Motivo (opcional)</label>
                    <textarea id="blockMotivo" name="motivo" rows="3" 
                              placeholder="Informe o motivo do bloqueio"></textarea>
                </div>
                
                <div class="form-footer">
                    <button type="button" class="btn cancelar" onclick="closeBlockModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn bloquear-confirm" id="btnBloquear">
                        <i class="fas fa-lock"></i> Bloquear
                    </button>
                </div>
            </form>
        </div>
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
let blockUserId = null;
let blockUserName = null;
let tipoBloqueioSelecionado = null;

// Modal de Edição
function openEditModal(userId) {
    fetch('usuarios_bank.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=edit&id=' + userId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('editUserId').value = data.usuario.id;
            document.getElementById('editNome').value = data.usuario.nome_completo;
            document.getElementById('editEmail').value = data.usuario.email;
            document.getElementById('editUsuario').value = data.usuario.usuario;
            document.getElementById('editModal').style.display = 'flex';
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Erro ao carregar usuário', 'error');
        console.error('Error:', error);
    });
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    document.getElementById('editUserForm').reset();
}

// Modal de Confirmação
function deleteUser(userId, userName) {
    currentUserId = userId;
    document.getElementById('confirmMessage').innerHTML = 
        `Tem certeza que deseja excluir o usuário <strong>"${userName}"</strong>?<br>
         <small>Esta ação não pode ser desfeita.</small>`;
    document.getElementById('confirmModal').style.display = 'flex';
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
    currentUserId = null;
}

function confirmDelete() {
    if (!currentUserId) return;
    
    fetch('usuarios_bank.php', {
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
        showToast('Erro ao excluir usuário', 'error');
        console.error('Error:', error);
        closeConfirmModal();
    });
}

// Modal de Bloqueio
function openBlockModal(userId, userName) {
    blockUserId = userId;
    blockUserName = userName;
    document.getElementById('blockUserDisplay').textContent = userName;
    document.getElementById('blockUserId').value = userId;
    document.getElementById('blockUserName').value = userName;
    
    // Resetar seleção
    resetarSelecaoTempo();
    document.getElementById('blockModal').style.display = 'flex';
}

function closeBlockModal() {
    document.getElementById('blockModal').style.display = 'none';
    document.getElementById('blockUserForm').reset();
    resetarSelecaoTempo();
    blockUserId = null;
    blockUserName = null;
    tipoBloqueioSelecionado = null;
}

function resetarSelecaoTempo() {
    // Remover seleção de todos os botões
    document.querySelectorAll('.tempo-btn').forEach(btn => {
        btn.classList.remove('selecionado');
    });
    
    // Esconder campos
    document.getElementById('campoQuantidade').style.display = 'none';
    document.getElementById('avisoPermanente').style.display = 'none';
    document.getElementById('btnBloquear').disabled = true;
    document.getElementById('btnBloquear').innerHTML = '<i class="fas fa-lock"></i> Bloquear';
}

function selecionarTempo(tipo) {
    // Remover seleção anterior
    document.querySelectorAll('.tempo-btn').forEach(btn => {
        btn.classList.remove('selecionado');
    });
    
    // Selecionar novo botão
    const btn = document.querySelector(`.tempo-btn[data-tipo="${tipo}"]`);
    btn.classList.add('selecionado');
    
    tipoBloqueioSelecionado = tipo;
    
    // Mostrar/ocultar campos conforme tipo
    if (tipo === 'permanente') {
        // Permanente - mostrar aviso
        document.getElementById('campoQuantidade').style.display = 'none';
        document.getElementById('avisoPermanente').style.display = 'block';
        document.getElementById('btnBloquear').innerHTML = '<i class="fas fa-ban"></i> Bloquear Permanentemente';
        document.getElementById('btnBloquear').disabled = false;
    } else {
        // Temporário - mostrar campo quantidade
        document.getElementById('campoQuantidade').style.display = 'block';
        document.getElementById('avisoPermanente').style.display = 'none';
        document.getElementById('btnBloquear').innerHTML = '<i class="fas fa-lock"></i> Bloquear';
        document.getElementById('btnBloquear').disabled = false;
        
        // Atualizar label da unidade
        const unidades = {
            'minutos': 'minutos',
            'horas': 'horas', 
            'dias': 'dias',
            'semanas': 'semanas',
            'meses': 'meses'
        };
        document.getElementById('unidadeTempo').textContent = unidades[tipo];
        
        // Focar no campo quantidade
        setTimeout(() => {
            document.getElementById('blockQuantidade').focus();
        }, 100);
    }
}

// Desbloquear usuário
function desbloquearUser(userId, userName) {
    if (confirm(`Deseja desbloquear o usuário "${userName}"?`)) {
        fetch('usuarios_bank.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=desbloquear&id=' + userId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message, 'error');
            }
        });
    }
}

// Formulário de Edição
document.getElementById('editUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'update');
    
    fetch('usuarios_bank.php', {
        method: 'POST',
        body: new URLSearchParams(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Erro ao atualizar usuário', 'error');
        console.error('Error:', error);
    });
});

// Formulário de bloqueio
document.getElementById('blockUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Verificar se é permanente
    if (tipoBloqueioSelecionado === 'permanente') {
        const confirmar = confirm(`ATENÇÃO! Você está bloqueando PERMANENTEMENTE o usuário "${blockUserName}".\n\nEsta ação NÃO poderá ser desfeita automaticamente.\n\nDeseja continuar?`);
        if (!confirmar) return;
    } else {
        // Verificar se quantidade foi preenchida
        const quantidade = document.getElementById('blockQuantidade').value;
        if (!quantidade || quantidade < 1) {
            showToast('Por favor, digite a quantidade', 'error');
            document.getElementById('blockQuantidade').focus();
            return;
        }
    }
    
    // Preparar dados
    const formData = new FormData(this);
    formData.append('action', 'bloquear');
    formData.append('tipo', tipoBloqueioSelecionado);
    
    // Se for temporário, enviar quantidade também
    if (tipoBloqueioSelecionado !== 'permanente') {
        formData.append('quantidade', document.getElementById('blockQuantidade').value);
    }
    
    // Enviar para o servidor
    fetch('usuarios_bank.php', {
        method: 'POST',
        body: new URLSearchParams(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
        closeBlockModal();
    })
    .catch(error => {
        showToast('Erro ao bloquear usuário', 'error');
        console.error('Error:', error);
        closeBlockModal();
    });
});

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
    const editModal = document.getElementById('editModal');
    const confirmModal = document.getElementById('confirmModal');
    const blockModal = document.getElementById('blockModal');
    
    if (event.target === editModal) {
        closeEditModal();
    }
    if (event.target === confirmModal) {
        closeConfirmModal();
    }
    if (event.target === blockModal) {
        closeBlockModal();
    }
}

// Fechar com ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeEditModal();
        closeConfirmModal();
        closeBlockModal();
    }
});

// Adicionar estilos inline
document.head.insertAdjacentHTML('beforeend', `
<style>
.status-bloqueado {
    background: linear-gradient(135deg, #ff3333 0%, #cc0000 100%);
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.status-ativo {
    background: linear-gradient(135deg, #00cc66 0%, #00994d 100%);
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn.bloquear {
    background: linear-gradient(135deg, #ff9900 0%, #cc6600 100%);
    color: white;
    border: 1px solid #ffaa00;
}

.btn.desbloquear {
    background: linear-gradient(135deg, #00cc66 0%, #00994d 100%);
    color: white;
    border: 1px solid #00dd77;
}

.tempo-botoes {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 15px;
}

.tempo-btn {
    padding: 12px;
    background: linear-gradient(135deg, #1b263b 0%, #24344d 100%);
    border: 2px solid #2a3a5a;
    border-radius: 8px;
    color: #dceaff;
    font-size: 0.9rem;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    transition: all 0.3s;
}

.tempo-btn:hover {
    background: linear-gradient(135deg, #2a3a5a 0%, #334466 100%);
    transform: translateY(-2px);
}

.tempo-btn.selecionado {
    background: linear-gradient(135deg, #4da3ff 0%, #2d8cff 100%);
    border-color: #5db0ff;
    color: white;
}

.tempo-btn.permanente {
    background: linear-gradient(135deg, #b30000 0%, #8b0000 100%);
    border-color: #d40000;
    color: white;
}

.tempo-btn.permanente.selecionado {
    background: linear-gradient(135deg, #ff3333 0%, #cc0000 100%);
    border-color: #ff5555;
}

#blockQuantidade {
    width: 100%;
    padding: 12px;
    background: rgba(255, 255, 255, 0.05);
    border: 2px solid #24344d;
    border-radius: 8px;
    color: #e6f0ff;
    font-size: 1rem;
    margin-top: 5px;
}

#blockQuantidade:focus {
    outline: none;
    border-color: #4da3ff;
    box-shadow: 0 0 0 3px rgba(77, 163, 255, 0.2);
}

#unidadeTempo {
    display: block;
    margin-top: 5px;
    color: #7a8ca5;
    font-size: 0.9rem;
}

.aviso {
    padding: 15px;
    border-radius: 8px;
    display: flex;
    gap: 10px;
    align-items: flex-start;
}

.aviso.alerta {
    background: linear-gradient(135deg, rgba(179, 0, 0, 0.2) 0%, rgba(139, 0, 0, 0.2) 100%);
    border: 1px solid #b30000;
    color: #ff9999;
}

.aviso i {
    font-size: 1.2rem;
    margin-top: 2px;
}

.aviso p {
    margin: 0;
    line-height: 1.4;
}
</style>
`);
</script>

</body>
</html>
