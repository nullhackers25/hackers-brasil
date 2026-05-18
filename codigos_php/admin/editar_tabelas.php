<?php
session_start();
require_once '../conexao.php';

// Verificar login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Buscar todas as tabelas
try {
    $sql = "SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_type = 'BASE TABLE' 
            ORDER BY table_name";
    
    $stmt = $conn->query($sql);
    $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $erro = "Erro ao buscar tabelas: " . $e->getMessage();
    $tabelas = [];
}

// Se uma tabela foi selecionada, redirecionar para a página de edição específica
if (isset($_GET['tabela']) && in_array($_GET['tabela'], $tabelas)) {
    header("Location: editar_tabela.php?tabela=" . urlencode($_GET['tabela']));
    exit;
}

// Se enviar o formulário de seleção
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tabela'])) {
    $tabela_selecionada = $_POST['tabela'];
    if (in_array($tabela_selecionada, $tabelas)) {
        header("Location: editar_tabela.php?tabela=" . urlencode($tabela_selecionada));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selecionar Tabela para Editar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        background: linear-gradient(135deg, #0d1117 0%, #1a2029 100%);
        min-height: 100vh;
        color: #e6f0ff;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .container {
        width: 100%;
        max-width: 800px;
    }
    
    .card {
        background: #1a2029;
        border-radius: 15px;
        padding: 40px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        border: 1px solid #2a3a5a;
    }
    
    .card-header {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .card-header h1 {
        font-size: 2rem;
        margin-bottom: 10px;
        background: linear-gradient(135deg, #4da3ff 0%, #2d8cff 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
    }
    
    .card-header p {
        color: #a0b3d6;
        font-size: 1.1rem;
    }
    
    /* Seletor de Tabelas */
    .seletor-container {
        margin-bottom: 30px;
    }
    
    .seletor-label {
        display: block;
        color: #dceaff;
        margin-bottom: 15px;
        font-weight: 500;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .seletor-tabelas {
        position: relative;
    }
    
    .seletor-tabelas select {
        width: 100%;
        padding: 16px 20px;
        background: rgba(255, 255, 255, 0.05);
        border: 2px solid #24344d;
        border-radius: 10px;
        color: #e6f0ff;
        font-size: 1.1rem;
        appearance: none;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .seletor-tabelas select:focus {
        outline: none;
        border-color: #4da3ff;
        box-shadow: 0 0 0 3px rgba(77, 163, 255, 0.2);
    }
    
    .seletor-tabelas:after {
        content: '▼';
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #a0b3d6;
        pointer-events: none;
        font-size: 0.9rem;
    }
    
    /* Informações da Tabela */
    .tabela-info {
        background: linear-gradient(135deg, #1b263b 0%, #24344d 100%);
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 30px;
        border: 1px solid #2a3a5a;
        display: none;
    }
    
    .tabela-info.mostrar {
        display: block;
    }
    
    .info-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .info-header h3 {
        color: #e6f0ff;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .tabela-stats {
        display: flex;
        gap: 15px;
    }
    
    .stat {
        background: rgba(255, 255, 255, 0.05);
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
        color: #a0b3d6;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    /* Botões de Ação */
    .botoes-acao {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }
    
    .btn {
        padding: 16px 30px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        font-size: 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        flex: 1;
    }
    
    .btn.voltar {
        background: linear-gradient(135deg, #1b263b 0%, #24344d 100%);
        color: #dceaff;
        border: 1px solid #2a3a5a;
    }
    
    .btn.editar {
        background: linear-gradient(135deg, #4da3ff 0%, #2d8cff 100%);
        color: white;
        border: 1px solid #5db0ff;
    }
    
    .btn.editar:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .btn:hover:not(:disabled) {
        transform: translateY(-3px);
    }
    
    /* Mensagem de erro */
    .mensagem-erro {
        background: linear-gradient(135deg, rgba(255, 77, 77, 0.1) 0%, rgba(204, 0, 0, 0.1) 100%);
        border: 1px solid #ff4d4d;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 30px;
        color: #ff9999;
        text-align: center;
    }
    
    /* Quando não há tabelas */
    .no-tables {
        text-align: center;
        padding: 40px 20px;
        color: #a0b3d6;
    }
    
    .no-tables i {
        font-size: 4rem;
        margin-bottom: 20px;
        color: #4da3ff;
        opacity: 0.5;
    }
    
    .no-tables p {
        font-size: 1.1rem;
        margin-bottom: 25px;
    }
    
    .btn-criar {
        background: linear-gradient(135deg, #00cc66 0%, #00994d 100%);
        color: white;
        padding: 14px 30px;
        border-radius: 10px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        font-size: 1rem;
    }
    
    /* Footer */
    .footer {
        text-align: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #2a3a5a;
        color: #7a8ca5;
        font-size: 0.9rem;
    }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1><i class="fas fa-edit"></i> Editar Tabela</h1>
                <p>Selecione uma tabela para modificar sua estrutura</p>
            </div>
            
            <?php if (isset($erro)): ?>
            <div class="mensagem-erro">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($erro); ?>
            </div>
            <?php endif; ?>
            
            <?php if (empty($tabelas)): ?>
            <div class="no-tables">
                <i class="fas fa-database"></i>
                <p>Nenhuma tabela encontrada no banco de dados.</p>
                <a href="criar_tabela.php" class="btn-criar">
                    <i class="fas fa-plus"></i> Criar Primeira Tabela
                </a>
            </div>
            <?php else: ?>
            <form id="formSelecionarTabela" method="POST" action="">
                <div class="seletor-container">
                    <label class="seletor-label">
                        <i class="fas fa-database"></i> Selecione a tabela para editar:
                    </label>
                    
                    <div class="seletor-tabelas">
                        <select id="seletorTabela" name="tabela" required onchange="carregarInfoTabela(this.value)">
                            <option value="">-- Selecione uma tabela --</option>
                            <?php foreach ($tabelas as $tabela): ?>
                            <option value="<?php echo htmlspecialchars($tabela); ?>">
                                <?php echo htmlspecialchars($tabela); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Informações da tabela selecionada -->
                <div id="infoTabela" class="tabela-info">
                    <div class="info-header">
                        <h3 id="nomeTabela">
                            <i class="fas fa-table"></i> <span id="nomeTabelaTexto"></span>
                        </h3>
                        <div class="tabela-stats">
                            <span class="stat" id="statColunas">
                                <i class="fas fa-columns"></i> <span id="totalColunas">0</span> colunas
                            </span>
                            <span class="stat" id="statRegistros">
                                <i class="fas fa-list"></i> <span id="totalRegistros">0</span> registros
                            </span>
                        </div>
                    </div>
                    <div id="detalhesTabela">
                        Carregando informações da tabela...
                    </div>
                </div>
                
                <div class="botoes-acao">
                    <a href="gerenciar_tabelas.php" class="btn voltar">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                    <button type="submit" id="btnEditar" class="btn editar" disabled>
                        <i class="fas fa-edit"></i> Editar Tabela Selecionada
                    </button>
                </div>
            </form>
            <?php endif; ?>
            
            <div class="footer">
                <p><i class="fas fa-info-circle"></i> Selecione uma tabela para ver detalhes e editá-la</p>
            </div>
        </div>
    </div>

    <script>
    // Carregar informações da tabela selecionada
    function carregarInfoTabela(tabelaNome) {
        const infoDiv = document.getElementById('infoTabela');
        const btnEditar = document.getElementById('btnEditar');
        
        if (!tabelaNome) {
            infoDiv.classList.remove('mostrar');
            btnEditar.disabled = true;
            return;
        }
        
        // Atualizar nome da tabela
        document.getElementById('nomeTabelaTexto').textContent = tabelaNome;
        
        // Mostrar loading
        document.getElementById('detalhesTabela').innerHTML = 
            '<div style="text-align: center; padding: 20px; color: #a0b3d6;">' +
            '<i class="fas fa-spinner fa-spin"></i> Carregando informações da tabela...' +
            '</div>';
        
        infoDiv.classList.add('mostrar');
        btnEditar.disabled = false;
        
        // Buscar informações da tabela via AJAX
        fetch(`ajax_info_tabela.php?tabela=${encodeURIComponent(tabelaNome)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Atualizar estatísticas
                    document.getElementById('totalColunas').textContent = data.total_colunas;
                    document.getElementById('totalRegistros').textContent = 
                        data.total_registros.toLocaleString();
                    
                    // Atualizar detalhes
                    let html = '<div style="margin-top: 15px;">';
                    
                    if (data.colunas && data.colunas.length > 0) {
                        html += '<p style="margin-bottom: 10px; color: #dceaff; font-weight: 500;">Colunas:</p>';
                        html += '<div style="display: flex; flex-wrap: wrap; gap: 8px;">';
                        
                        data.colunas.forEach(coluna => {
                            let tipoClass = '';
                            if (coluna.tipo.includes('int') || coluna.tipo.includes('num')) {
                                tipoClass = 'background: rgba(77, 163, 255, 0.1); border-color: #4da3ff; color: #a0d2ff;';
                            } else if (coluna.tipo.includes('char') || coluna.tipo.includes('text')) {
                                tipoClass = 'background: rgba(0, 204, 102, 0.1); border-color: #00cc66; color: #99ffcc;';
                            } else if (coluna.tipo.includes('date') || coluna.tipo.includes('time')) {
                                tipoClass = 'background: rgba(255, 193, 7, 0.1); border-color: #ffc107; color: #ffd966;';
                            } else if (coluna.tipo.includes('bool')) {
                                tipoClass = 'background: rgba(155, 89, 182, 0.1); border-color: #9b59b6; color: #d6a2e8;';
                            } else {
                                tipoClass = 'background: rgba(255, 255, 255, 0.05); border-color: #2a3a5a; color: #a0b3d6;';
                            }
                            
                            html += `<span style="padding: 4px 10px; border-radius: 4px; border: 1px solid; font-size: 0.85rem; ${tipoClass}" 
                                     title="${coluna.nome}: ${coluna.tipo}${coluna.tamanho ? '(' + coluna.tamanho + ')' : ''} ${coluna.nulo === 'YES' ? 'NULL' : 'NOT NULL'}">
                                     ${coluna.nome}
                                     </span>`;
                        });
                        
                        html += '</div>';
                    } else {
                        html += '<p style="color: #a0b3d6;">Nenhuma informação disponível sobre as colunas.</p>';
                    }
                    
                    html += '</div>';
                    document.getElementById('detalhesTabela').innerHTML = html;
                    
                } else {
                    document.getElementById('detalhesTabela').innerHTML = 
                        `<div style="color: #ff9999; text-align: center; padding: 20px;">
                            <i class="fas fa-exclamation-circle"></i> Erro ao carregar informações
                        </div>`;
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                document.getElementById('detalhesTabela').innerHTML = 
                    `<div style="color: #ff9999; text-align: center; padding: 20px;">
                        <i class="fas fa-exclamation-circle"></i> Erro ao carregar informações da tabela
                    </div>`;
            });
    }
    
    // Carregar informações quando a página carrega
    document.addEventListener('DOMContentLoaded', function() {
        const seletor = document.getElementById('seletorTabela');
        if (seletor.value) {
            carregarInfoTabela(seletor.value);
        }
    });
    </script>
</body>
</html>
