<?php
session_start();
require_once '../conexao.php';

// Impedir acesso sem login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Verificar qual tabela estamos gerenciando
$tabela = $_GET['tabela'] ?? 'usuarios';
$tabela = filter_var($tabela, FILTER_SANITIZE_STRING);

// Buscar estrutura da tabela
try {
    // Para PostgreSQL
    $sql = "SELECT 
                column_name as nome_coluna,
                data_type as tipo_dado,
                character_maximum_length as tamanho,
                is_nullable as pode_nulo,
                column_default as valor_padrao
            FROM information_schema.columns 
            WHERE table_name = :tabela 
            ORDER BY ordinal_position";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':tabela', $tabela);
    $stmt->execute();
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar total de registros
    $sql_registros = "SELECT COUNT(*) FROM $tabela";
    $stmt_registros = $conn->query($sql_registros);
    $total_registros = $stmt_registros->fetchColumn();
    
} catch (PDOException $e) {
    $colunas = [];
    $erro = "Erro ao buscar estrutura da tabela: " . $e->getMessage();
}

// Processar adição de coluna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => ''];
    
    if ($_POST['action'] === 'add_column') {
        $nome_coluna = $_POST['nome_coluna'] ?? '';
        $tipo_dado = $_POST['tipo_dado'] ?? '';
        $tamanho = $_POST['tamanho'] ?? null;
        $pode_nulo = isset($_POST['pode_nulo']) && $_POST['pode_nulo'] === 'on' ? 'YES' : 'NO';
        $valor_padrao = $_POST['valor_padrao'] ?? null;
        
        if ($nome_coluna && $tipo_dado) {
            try {
                // Montar SQL para adicionar coluna
                $sql = "ALTER TABLE $tabela ADD COLUMN $nome_coluna $tipo_dado";
                
                if ($tamanho && in_array($tipo_dado, ['varchar', 'char'])) {
                    $sql .= "($tamanho)";
                }
                
                $sql .= " " . ($pode_nulo === 'YES' ? 'NULL' : 'NOT NULL');
                
                if ($valor_padrao) {
                    $sql .= " DEFAULT '$valor_padrao'";
                }
                
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                
                // Log da alteração
                logAlteracao($tabela, "ADD COLUMN $nome_coluna $tipo_dado", $_SESSION['admin_id']);
                
                $response['success'] = true;
                $response['message'] = 'Coluna adicionada com sucesso!';
                $response['reload'] = true;
                
            } catch (PDOException $e) {
                $response['message'] = 'Erro ao adicionar coluna: ' . $e->getMessage();
            }
        } else {
            $response['message'] = 'Nome da coluna e tipo são obrigatórios';
        }
        
        echo json_encode($response);
        exit;
    }
    
    if ($_POST['action'] === 'rename_column') {
        $nome_antigo = $_POST['nome_antigo'] ?? '';
        $nome_novo = $_POST['nome_novo'] ?? '';
        
        if ($nome_antigo && $nome_novo) {
            try {
                $sql = "ALTER TABLE $tabela RENAME COLUMN $nome_antigo TO $nome_novo";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                
                logAlteracao($tabela, "RENAME COLUMN $nome_antigo TO $nome_novo", $_SESSION['admin_id']);
                
                $response['success'] = true;
                $response['message'] = 'Coluna renomeada com sucesso!';
                $response['reload'] = true;
                
            } catch (PDOException $e) {
                $response['message'] = 'Erro ao renomear coluna: ' . $e->getMessage();
            }
        }
        
        echo json_encode($response);
        exit;
    }
    
    if ($_POST['action'] === 'drop_column') {
        $nome_coluna = $_POST['nome_coluna'] ?? '';
        
        if ($nome_coluna) {
            try {
                $sql = "ALTER TABLE $tabela DROP COLUMN $nome_coluna";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                
                logAlteracao($tabela, "DROP COLUMN $nome_coluna", $_SESSION['admin_id']);
                
                $response['success'] = true;
                $response['message'] = 'Coluna removida com sucesso!';
                $response['reload'] = true;
                
            } catch (PDOException $e) {
                $response['message'] = 'Erro ao remover coluna: ' . $e->getMessage();
            }
        }
        
        echo json_encode($response);
        exit;
    }
}

// Função para log de alterações
function logAlteracao($tabela, $operacao, $admin_id) {
    $log = date('Y-m-d H:i:s') . " | Tabela: $tabela | Operação: $operacao | Admin ID: $admin_id\n";
    file_put_contents('logs/alteracoes_banco.log', $log, FILE_APPEND);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Tabela: <?= htmlspecialchars($tabela) ?></title>
    <link rel="stylesheet" href="usuarios_bank.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* Estilos específicos para gerenciamento de tabela */
    .container {
        max-width: 1200px;
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
    
    .btn.editar-tabela {
        background: linear-gradient(135deg, #4da3ff 0%, #2d8cff 100%);
        color: white;
        border: 1px solid #5db0ff;
    }
    
    .card-estrutura {
        background: #1a2029;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
    }
    
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 1px solid #2a3a5a;
        padding-bottom: 10px;
    }
    
    .card-header h3 {
        margin: 0;
        color: #e6f0ff;
        font-size: 1.3rem;
    }
    
    .tabela-estrutura {
        width: 100%;
        border-collapse: collapse;
    }
    
    .tabela-estrutura th {
        background: linear-gradient(135deg, #1b263b 0%, #24344d 100%);
        color: #dceaff;
        padding: 12px 15px;
        text-align: left;
        border-bottom: 2px solid #2a3a5a;
        font-weight: 600;
    }
    
    .tabela-estrutura td {
        padding: 12px 15px;
        border-bottom: 1px solid #2a3a5a;
        color: #a0b3d6;
    }
    
    .tabela-estrutura tr:hover {
        background: rgba(77, 163, 255, 0.05);
    }
    
    .badge-tipo {
        background: linear-gradient(135deg, #4da3ff 0%, #2d8cff 100%);
        color: white;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.8rem;
        display: inline-block;
    }
    
    .badge-nulo {
        background: linear-gradient(135deg, #00cc66 0%, #00994d 100%);
        color: white;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.8rem;
        display: inline-block;
    }
    
    .badge-not-nulo {
        background: linear-gradient(135deg, #ff9900 0%, #cc6600 100%);
        color: white;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.8rem;
        display: inline-block;
    }
    
    .acoes-coluna {
        display: flex;
        gap: 8px;
    }
    
    .btn-acao {
        padding: 6px 12px;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.3s;
    }
    
    .btn-acao.renomear {
        background: linear-gradient(135deg, #ffcc00 0%, #e6b800 100%);
        color: #000;
    }
    
    .btn-acao.remover {
        background: linear-gradient(135deg, #ff4d4d 0%, #cc0000 100%);
        color: white;
    }
    
    /* Seção de adicionar coluna */
    .secao-nova-coluna {
        background: #1a2029;
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 30px;
        border: 1px solid #2a3a5a;
    }
    
    .secao-nova-coluna h3 {
        margin-top: 0;
        color: #e6f0ff;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
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
    }
    
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 12px;
        background: rgba(255, 255, 255, 0.05);
        border: 2px solid #24344d;
        border-radius: 8px;
        color: #e6f0ff;
        font-size: 1rem;
    }
    
    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #4da3ff;
        box-shadow: 0 0 0 3px rgba(77, 163, 255, 0.2);
    }
    
    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 10px;
    }
    
    .checkbox-group input[type="checkbox"] {
        width: auto;
    }
    
    .form-footer {
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        margin-top: 20px;
    }
    
    .btn.salvar {
        background: linear-gradient(135deg, #00cc66 0%, #00994d 100%);
        color: white;
        padding: 12px 25px;
    }
    
    .btn.cancelar {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        color: white;
        padding: 12px 25px;
    }
    
    /* Modal de renomear */
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
        max-width: 500px;
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
    
    .modal-header h3 {
        margin: 0;
        color: #e6f0ff;
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
    
    .aviso {
        background: rgba(255, 193, 7, 0.1);
        border: 1px solid #ffc107;
        border-radius: 8px;
        padding: 15px;
        margin: 20px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .aviso i {
        color: #ffc107;
        font-size: 1.2rem;
    }
    
    .aviso p {
        margin: 0;
        color: #ffd966;
    }
    
    /* Estatísticas da tabela */
    .stats-tabela {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .stat-item {
        background: linear-gradient(135deg, #1b263b 0%, #24344d 100%);
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        border: 1px solid #2a3a5a;
    }
    
    .stat-numero {
        font-size: 1.8rem;
        font-weight: bold;
        color: #e6f0ff;
        margin: 5px 0;
    }
    
    .stat-label {
        color: #a0b3d6;
        font-size: 0.9rem;
    }
    </style>
</head>
<body>

<div class="container">
    <h1><i class="fas fa-table"></i> Editar Tabela: <?= htmlspecialchars($tabela) ?></h1>
    
    <div class="painel-acoes">
        <a href="usuarios_bank.php" class="btn voltar">
            <i class="fas fa-arrow-left"></i> Voltar para Usuários
        </a>
        <a href="gerenciar_tabelas.php" class="btn voltar">
            <i class="fas fa-database"></i> Todas as Tabelas
        </a>
        <button class="btn editar-tabela" onclick="location.reload()">
            <i class="fas fa-sync"></i> Atualizar
        </button>
    </div>
    
    <!-- Estatísticas da tabela -->
    <div class="stats-tabela">
        <div class="stat-item">
            <div class="stat-numero"><?= count($colunas) ?></div>
            <div class="stat-label">Total de Colunas</div>
        </div>
        <div class="stat-item">
            <div class="stat-numero"><?= number_format($total_registros) ?></div>
            <div class="stat-label">Usuários Cadastrados</div>
        </div>
        <div class="stat-item">
            <div class="stat-numero"><?= htmlspecialchars($tabela) ?></div>
            <div class="stat-label">Nome da Tabela</div>
        </div>
        <div class="stat-item">
            <div class="stat-numero">PostgreSQL</div>
            <div class="stat-label">Sistema de Banco</div>
        </div>
    </div>
    
    <div class="card-estrutura">
        <div class="card-header">
            <h3><i class="fas fa-columns"></i> Estrutura da Tabela</h3>
            <span class="badge-tipo"><?= count($colunas) ?> colunas</span>
        </div>
        
        <?php if (!empty($colunas)): ?>
        <table class="tabela-estrutura">
            <thead>
                <tr>
                    <th>Nome da Coluna</th>
                    <th>Tipo de Dado</th>
                    <th>Tamanho</th>
                    <th>Nulo?</th>
                    <th>Valor Padrão</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($colunas as $coluna): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($coluna['nome_coluna']) ?></strong></td>
                    <td><span class="badge-tipo"><?= htmlspecialchars($coluna['tipo_dado']) ?></span></td>
                    <td><?= $coluna['tamanho'] ? htmlspecialchars($coluna['tamanho']) : '-' ?></td>
                    <td>
                        <?php if ($coluna['pode_nulo'] === 'YES'): ?>
                        <span class="badge-nulo">Pode ser NULO</span>
                        <?php else: ?>
                        <span class="badge-not-nulo">NOT NULL</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $coluna['valor_padrao'] ? htmlspecialchars($coluna['valor_padrao']) : '-' ?></td>
                    <td>
                        <div class="acoes-coluna">
                            <button class="btn-acao renomear" onclick="abrirModalRenomear('<?= htmlspecialchars($coluna['nome_coluna']) ?>')">
                                <i class="fas fa-edit"></i> Renomear
                            </button>
                            <?php if ($coluna['nome_coluna'] !== 'id'): ?>
                            <button class="btn-acao remover" onclick="removerColuna('<?= htmlspecialchars($coluna['nome_coluna']) ?>')">
                                <i class="fas fa-trash"></i> Remover
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="text-align: center; padding: 40px; color: #a0b3d6;">
            <i class="fas fa-exclamation-circle" style="font-size: 3rem; margin-bottom: 15px;"></i>
            <p>Nenhuma coluna encontrada ou erro ao carregar estrutura.</p>
            <?php if (isset($erro)): ?>
            <p style="color: #ff6666;"><?= htmlspecialchars($erro) ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="aviso">
        <i class="fas fa-exclamation-triangle"></i>
        <p><strong>Atenção:</strong> Alterações na estrutura da tabela podem afetar o funcionamento do sistema. Certifique-se de que não há dados importantes nas colunas que serão removidas. A coluna 'id' não pode ser removida.</p>
    </div>
    
    <div class="secao-nova-coluna">
        <h3><i class="fas fa-plus-circle"></i> Adicionar Nova Coluna</h3>
        
        <form id="formNovaColuna">
            <div class="form-grid">
                <div class="form-group">
                    <label for="nome_coluna"><i class="fas fa-tag"></i> Nome da Coluna</label>
                    <input type="text" id="nome_coluna" name="nome_coluna" required 
                           placeholder="ex: telefone, status, data_expiracao"
                           pattern="[a-zA-Z_][a-zA-Z0-9_]*"
                           title="Comece com letra, pode conter letras, números e underline">
                </div>
                
                <div class="form-group">
                    <label for="tipo_dado"><i class="fas fa-code"></i> Tipo de Dado</label>
                    <select id="tipo_dado" name="tipo_dado" required>
                        <option value="">Selecione um tipo...</option>
                        <option value="VARCHAR">Texto (VARCHAR)</option>
                        <option value="TEXT">Texto Longo (TEXT)</option>
                        <option value="INTEGER">Número Inteiro (INTEGER)</option>
                        <option value="BIGINT">Número Grande (BIGINT)</option>
                        <option value="DECIMAL">Número Decimal (DECIMAL)</option>
                        <option value="BOOLEAN">Verdadeiro/Falso (BOOLEAN)</option>
                        <option value="DATE">Data (DATE)</option>
                        <option value="TIMESTAMP">Data e Hora (TIMESTAMP)</option>
                        <option value="TIME">Hora (TIME)</option>
                    </select>
                </div>
                
                <div class="form-group" id="tamanhoGroup" style="display: none;">
                    <label for="tamanho"><i class="fas fa-ruler"></i> Tamanho Máximo</label>
                    <input type="number" id="tamanho" name="tamanho" 
                           min="1" max="65535" value="255"
                           placeholder="ex: 100">
                    <small style="color: #7a8ca5;">Para VARCHAR, use 255 como padrão</small>
                </div>
                
                <div class="form-group">
                    <label for="valor_padrao"><i class="fas fa-cog"></i> Valor Padrão (opcional)</label>
                    <input type="text" id="valor_padrao" name="valor_padrao" 
                           placeholder="ex: NULL, 0, 'ativo', now()">
                </div>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="pode_nulo" name="pode_nulo">
                <label for="pode_nulo" style="margin: 0;">Permitir valores nulos (NULL)</label>
            </div>
            
            <div class="form-footer">
                <button type="button" class="btn cancelar" onclick="limparFormNovaColuna()">
                    <i class="fas fa-times"></i> Limpar
                </button>
                <button type="submit" class="btn salvar">
                    <i class="fas fa-plus"></i> Adicionar Coluna
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para renomear coluna -->
<div id="modalRenomear" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Renomear Coluna</h3>
            <button class="close-modal" onclick="fecharModalRenomear()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formRenomear">
                <input type="hidden" id="nome_antigo" name="nome_antigo">
                
                <div class="form-group">
                    <label for="nome_novo">Novo Nome da Coluna</label>
                    <input type="text" id="nome_novo" name="nome_novo" required 
                           pattern="[a-zA-Z_][a-zA-Z0-9_]*"
                           title="Comece com letra, pode conter letras, números e underline">
                </div>
                
                <div class="form-footer">
                    <button type="button" class="btn cancelar" onclick="fecharModalRenomear()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn salvar">
                        <i class="fas fa-save"></i> Renomear
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast de Notificação -->
<div id="toast" class="toast"></div>

<script>
// Mostrar/ocultar campo tamanho baseado no tipo de dado
document.getElementById('tipo_dado').addEventListener('change', function() {
    const tipo = this.value;
    const tamanhoGroup = document.getElementById('tamanhoGroup');
    
    // Mostrar campo tamanho apenas para VARCHAR
    if (tipo === 'VARCHAR') {
        tamanhoGroup.style.display = 'block';
    } else {
        tamanhoGroup.style.display = 'none';
    }
});

// Formulário de nova coluna
document.getElementById('formNovaColuna').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'add_column');
    formData.append('tabela', '<?= $tabela ?>');
    
    // Se não for VARCHAR, não enviar tamanho
    if (document.getElementById('tipo_dado').value !== 'VARCHAR') {
        formData.delete('tamanho');
    }
    
    fetch('alterar_tabela.php', {
        method: 'POST',
        body: new URLSearchParams(formData)
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
    })
    .catch(error => {
        mostrarToast('Erro ao adicionar coluna', 'error');
        console.error('Error:', error);
    });
});

// Limpar formulário de nova coluna
function limparFormNovaColuna() {
    document.getElementById('formNovaColuna').reset();
    document.getElementById('tamanhoGroup').style.display = 'none';
}

// Funções para renomear coluna
let colunaParaRenomear = '';

function abrirModalRenomear(nomeColuna) {
    colunaParaRenomear = nomeColuna;
    document.getElementById('nome_antigo').value = nomeColuna;
    document.getElementById('nome_novo').value = nomeColuna;
    document.getElementById('modalRenomear').style.display = 'flex';
    setTimeout(() => {
        document.getElementById('nome_novo').focus();
        document.getElementById('nome_novo').select();
    }, 100);
}

function fecharModalRenomear() {
    document.getElementById('modalRenomear').style.display = 'none';
    colunaParaRenomear = '';
}

// Formulário de renomear
document.getElementById('formRenomear').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'rename_column');
    formData.append('tabela', '<?= $tabela ?>');
    
    fetch('alterar_tabela.php', {
        method: 'POST',
        body: new URLSearchParams(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarToast(data.message, 'success');
            fecharModalRenomear();
            if (data.reload) {
                setTimeout(() => location.reload(), 1500);
            }
        } else {
            mostrarToast(data.message, 'error');
        }
    })
    .catch(error => {
        mostrarToast('Erro ao renomear coluna', 'error');
        console.error('Error:', error);
    });
});

// Remover coluna
function removerColuna(nomeColuna) {
    if (!confirm(`ATENÇÃO! Você está prestes a remover a coluna "${nomeColuna}" da tabela <?= $tabela ?>.\n\nEsta ação NÃO pode ser desfeita e pode causar perda de dados.\n\nDeseja continuar?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'drop_column');
    formData.append('tabela', '<?= $tabela ?>');
    formData.append('nome_coluna', nomeColuna);
    
    fetch('alterar_tabela.php', {
        method: 'POST',
        body: new URLSearchParams(formData)
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
    })
    .catch(error => {
        mostrarToast('Erro ao remover coluna', 'error');
        console.error('Error:', error);
    });
}

// Toast notification
function mostrarToast(mensagem, tipo = 'info') {
    const toast = document.getElementById('toast');
    toast.textContent = mensagem;
    toast.className = 'toast show ' + tipo;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const modal = document.getElementById('modalRenomear');
    if (event.target === modal) {
        fecharModalRenomear();
    }
}

// Fechar com ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        fecharModalRenomear();
    }
});
</script>

</body>
</html>
