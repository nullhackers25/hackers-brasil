<?php
// editor_sql.php
session_start();
require_once '../conexao.php';

// Verificar login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Obter nome da tabela se fornecido
$tabela = $_GET['tabela'] ?? null;
$tabela_nome = $tabela ? htmlspecialchars($tabela) : null;

// Inicializar histórico na sessão
if (!isset($_SESSION['sql_historico'])) {
    $_SESSION['sql_historico'] = [];
}

// Processar execução SQL se for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = $_POST['sql'] ?? '';
    
    if (!empty($sql)) {
        try {
            $stmt = $conn->query($sql);
            $sql_lower = strtolower(trim($sql));
            
            // Adicionar ao histórico (máximo 100 comandos)
            array_unshift($_SESSION['sql_historico'], [
                'sql' => $sql,
                'data' => date('d/m/Y H:i:s'),
                'sucesso' => true
            ]);
            
            // Manter apenas últimos 100
            $_SESSION['sql_historico'] = array_slice($_SESSION['sql_historico'], 0, 100);
            
            if (strpos($sql_lower, 'select') === 0 || 
                strpos($sql_lower, 'show') === 0 || 
                strpos($sql_lower, 'describe') === 0 ||
                strpos($sql_lower, 'explain') === 0) {
                $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $mensagem_sucesso = "✅ Consulta executada! Resultados: " . count($resultados) . " linha(s)";
                $tipo_resultado = 'select';
            } else {
                $affected = $stmt->rowCount();
                $mensagem_sucesso = "✅ Comando executado com sucesso!" . ($affected > 0 ? " Linhas afetadas: $affected" : "");
                $tipo_resultado = 'comando';
                $resultados = [];
            }
            $erro = null;
        } catch (PDOException $e) {
            $erro = $e->getMessage();
            $mensagem_sucesso = null;
            $resultados = [];
            
            // Adicionar erro ao histórico
            array_unshift($_SESSION['sql_historico'], [
                'sql' => $sql,
                'data' => date('d/m/Y H:i:s'),
                'sucesso' => false,
                'erro' => $e->getMessage()
            ]);
            
            $_SESSION['sql_historico'] = array_slice($_SESSION['sql_historico'], 0, 100);
        }
    }
}

// Limpar histórico se solicitado
if (isset($_GET['limpar_historico'])) {
    $_SESSION['sql_historico'] = [];
    header("Location: editor_sql.php" . ($tabela ? "?tabela=" . urlencode($tabela) : ""));
    exit;
}

// Carregar comando do histórico se solicitado
$sql_carregado = '';
if (isset($_GET['carregar'])) {
    $index = (int)$_GET['carregar'];
    if (isset($_SESSION['sql_historico'][$index])) {
        $sql_carregado = $_SESSION['sql_historico'][$index]['sql'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor SQL - NeonDB</title>
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
        overflow-x: hidden;
    }
    
    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
        display: flex;
        flex-direction: column;
        height: 100vh;
        gap: 20px;
    }
    
    /* Header */
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #2a3a5a;
        flex-shrink: 0;
    }
    
    .header h1 {
        font-size: 1.8rem;
        background: linear-gradient(135deg, #4da3ff 0%, #2d8cff 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .nav-buttons {
        display: flex;
        gap: 10px;
    }
    
    .btn {
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        font-size: 0.95rem;
    }
    
    .btn-voltar {
        background: linear-gradient(135deg, #1b263b 0%, #24344d 100%);
        color: #dceaff;
        border: 1px solid #2a3a5a;
    }
    
    .btn-executar {
        background: linear-gradient(135deg, #00cc66 0%, #00994d 100%);
        color: white;
    }
    
    .btn-limpar {
        background: linear-gradient(135deg, #ff9900 0%, #cc6600 100%);
        color: white;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }
    
    /* Área Principal - LADO A LADO */
    .main-wrapper {
        display: flex;
        flex: 1;
        gap: 20px;
        overflow: hidden;
    }
    
    /* Painel Esquerdo - Editor */
    .editor-panel {
        flex: 2;
        display: flex;
        flex-direction: column;
        min-width: 0;
        background: linear-gradient(135deg, #1b263b 0%, #24344d 100%);
        border-radius: 10px;
        border: 1px solid #2a3a5a;
        overflow: hidden;
    }
    
    .editor-header {
        padding: 20px;
        border-bottom: 1px solid #2a3a5a;
        background: rgba(0, 0, 0, 0.2);
        flex-shrink: 0;
    }
    
    .editor-header h2 {
        color: #e6f0ff;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
    }
    
    .editor-tools {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }
    
    /* Editor SQL */
    .editor-container {
        flex: 1;
        padding: 20px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    
    #sql-editor {
        flex: 1;
        background: #0d1117;
        border: 2px solid #2a3a5a;
        border-radius: 8px;
        color: #e6f0ff;
        font-family: 'Fira Code', 'Courier New', monospace;
        font-size: 15px;
        line-height: 1.6;
        padding: 20px;
        resize: none;
        outline: none;
        transition: border-color 0.3s;
        min-height: 300px;
    }
    
    #sql-editor:focus {
        border-color: #4da3ff;
        box-shadow: 0 0 0 2px rgba(77, 163, 255, 0.2);
    }
    
    /* Painel Direito - Histórico */
    .historico-panel {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 400px;
        background: linear-gradient(135deg, #1b263b 0%, #24344d 100%);
        border-radius: 10px;
        border: 1px solid #2a3a5a;
        overflow: hidden;
    }
    
    .historico-header {
        padding: 20px;
        border-bottom: 1px solid #2a3a5a;
        background: rgba(0, 0, 0, 0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }
    
    .historico-header h3 {
        color: #e6f0ff;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .historico-content {
        flex: 1;
        overflow-y: auto;
        padding: 0;
    }
    
    /* Lista do Histórico */
    .historico-lista {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .historico-item {
        padding: 15px 20px;
        border-bottom: 1px solid rgba(42, 58, 90, 0.5);
        transition: all 0.3s;
        cursor: pointer;
        position: relative;
    }
    
    .historico-item:hover {
        background: rgba(77, 163, 255, 0.1);
    }
    
    .historico-item.sucesso {
        border-left: 4px solid #00cc66;
    }
    
    .historico-item.erro {
        border-left: 4px solid #ff4d4d;
    }
    
    .historico-sql {
        color: #e6f0ff;
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        margin-bottom: 8px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .historico-info {
        display: flex;
        justify-content: space-between;
        color: #a0b3d6;
        font-size: 0.8rem;
    }
    
    .historico-data {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .historico-status {
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .historico-status.sucesso {
        background: rgba(0, 204, 102, 0.2);
        color: #00ff88;
    }
    
    .historico-status.erro {
        background: rgba(255, 77, 77, 0.2);
        color: #ff9999;
    }
    
    /* Mensagens */
    .mensagem {
        padding: 15px 20px;
        border-radius: 8px;
        margin: 0 20px 20px 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.95rem;
        flex-shrink: 0;
    }
    
    .mensagem.sucesso {
        background: rgba(0, 204, 102, 0.15);
        border: 1px solid #00cc66;
        color: #00ff88;
    }
    
    .mensagem.erro {
        background: rgba(255, 77, 77, 0.15);
        border: 1px solid #ff4d4d;
        color: #ff9999;
    }
    
    /* Resultados (quando houver) */
    .resultados-container {
        margin-top: 20px;
        background: #0d1117;
        border-radius: 8px;
        border: 1px solid #2a3a5a;
        overflow: hidden;
        max-height: 400px;
        display: flex;
        flex-direction: column;
    }
    
    .resultados-header {
        padding: 12px 15px;
        background: rgba(0, 0, 0, 0.2);
        border-bottom: 1px solid #2a3a5a;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.9rem;
        color: #a0b3d6;
    }
    
    .resultados-content {
        flex: 1;
        overflow: auto;
        padding: 0;
    }
    
    /* Tabela de Resultados */
    .tabela-resultados {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
    }
    
    .tabela-resultados th {
        background: rgba(77, 163, 255, 0.15);
        color: #4da3ff;
        padding: 10px 12px;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid #2a3a5a;
        position: sticky;
        top: 0;
        white-space: nowrap;
    }
    
    .tabela-resultados td {
        padding: 8px 12px;
        color: #e6f0ff;
        border-bottom: 1px solid rgba(42, 58, 90, 0.5);
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .tabela-resultados tr:hover {
        background: rgba(77, 163, 255, 0.05);
    }
    
    /* Exemplos */
    .exemplos-container {
        margin-top: 20px;
        padding: 15px;
        background: rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        border: 1px solid #2a3a5a;
    }
    
    .exemplos-container h4 {
        color: #a0b3d6;
        margin-bottom: 10px;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .exemplos-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .btn-exemplo {
        background: rgba(77, 163, 255, 0.1);
        border: 1px solid rgba(77, 163, 255, 0.3);
        color: #a0b3d6;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }
    
    .btn-exemplo:hover {
        background: rgba(77, 163, 255, 0.2);
        border-color: #4da3ff;
        color: #e6f0ff;
    }
    
    /* Scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
    }
    
    ::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.1);
        border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb {
        background: rgba(77, 163, 255, 0.3);
        border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: rgba(77, 163, 255, 0.5);
    }
    
    /* Responsividade */
    @media (max-width: 1200px) {
        .main-wrapper {
            flex-direction: column;
        }
        
        .historico-panel {
            min-width: 100%;
            height: 300px;
        }
    }
    
    @media (max-width: 768px) {
        .header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }
        
        .nav-buttons {
            width: 100%;
            justify-content: space-between;
        }
        
        .btn {
            padding: 8px 15px;
            font-size: 0.85rem;
        }
    }
    
    /* Vazio no histórico */
    .historico-vazio {
        padding: 40px 20px;
        text-align: center;
        color: #a0b3d6;
        font-style: italic;
    }
    
    .historico-vazio i {
        font-size: 2rem;
        margin-bottom: 10px;
        opacity: 0.5;
    }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-code"></i> Editor SQL</h1>
            <div class="nav-buttons">
                <a href="gerenciar_tabelas.php" class="btn btn-voltar">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
                <button type="submit" form="sql-form" class="btn btn-executar">
                    <i class="fas fa-play"></i> Executar (Ctrl+Enter)
                </button>
                <button type="button" onclick="limparEditor()" class="btn btn-limpar">
                    <i class="fas fa-eraser"></i> Limpar
                </button>
            </div>
        </div>
        
        <!-- Área Principal -->
        <div class="main-wrapper">
            <!-- Painel Esquerdo - Editor -->
            <form id="sql-form" method="POST" class="editor-panel">
                <div class="editor-header">
                    <h2><i class="fas fa-terminal"></i> Digite seu SQL</h2>
                    
                    <!-- Exemplos Rápidos -->
                    <div class="exemplos-container">
                        <h4><i class="fas fa-bolt"></i> Comandos rápidos:</h4>
                        <div class="exemplos-buttons">
                            <button type="button" onclick="carregarExemplo('select')" class="btn-exemplo">
                                <i class="fas fa-search"></i> SELECT
                            </button>
                            <button type="button" onclick="carregarExemplo('create')" class="btn-exemplo">
                                <i class="fas fa-plus"></i> CREATE
                            </button>
                            <button type="button" onclick="carregarExemplo('insert')" class="btn-exemplo">
                                <i class="fas fa-plus-circle"></i> INSERT
                            </button>
                            <button type="button" onclick="carregarExemplo('update')" class="btn-exemplo">
                                <i class="fas fa-edit"></i> UPDATE
                            </button>
                            <button type="button" onclick="carregarExemplo('alter')" class="btn-exemplo">
                                <i class="fas fa-wrench"></i> ALTER
                            </button>
                            <button type="button" onclick="carregarExemplo('tabelas')" class="btn-exemplo">
                                <i class="fas fa-list"></i> Ver Tabelas
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="editor-container">
                    <textarea id="sql-editor" name="sql" placeholder="<?php
if ($tabela_nome) {
    echo "-- Tabela: $tabela_nome\n-- Exemplo: SELECT * FROM \"$tabela_nome\" LIMIT 10;";
} else {
    echo "-- Digite seus comandos SQL aqui...\n-- Pressione Ctrl+Enter para executar\n-- Use os botões acima para exemplos rápidos";
}
?>"><?php 
echo $sql_carregado ?: ($tabela_nome ? "-- Tabela: $tabela_nome\nSELECT * FROM \"$tabela_nome\" LIMIT 10;" : "");
?></textarea>
                    
                    <!-- Mensagens do PHP -->
                    <?php if (isset($mensagem_sucesso)): ?>
                    <div class="mensagem sucesso">
                        <i class="fas fa-check-circle"></i> <?php echo $mensagem_sucesso; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($erro)): ?>
                    <div class="mensagem erro">
                        <i class="fas fa-exclamation-circle"></i> Erro: <?php echo htmlspecialchars($erro); ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Resultados -->
                    <?php if (isset($resultados) && !empty($resultados) && isset($tipo_resultado) && $tipo_resultado === 'select'): ?>
                    <div class="resultados-container">
                        <div class="resultados-header">
                            <span><i class="fas fa-table"></i> Resultados (<?php echo count($resultados); ?> linhas)</span>
                            <button type="button" onclick="exportarResultados()" class="btn-exemplo" style="padding: 4px 8px;">
                                <i class="fas fa-download"></i> CSV
                            </button>
                        </div>
                        <div class="resultados-content">
                            <table class="tabela-resultados">
                                <thead>
                                    <tr>
                                        <?php foreach (array_keys($resultados[0]) as $coluna): ?>
                                            <th><?php echo htmlspecialchars($coluna); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resultados as $linha): ?>
                                        <tr>
                                            <?php foreach ($linha as $valor): ?>
                                                <td title="<?php echo htmlspecialchars($valor); ?>">
                                                    <?php 
                                                    if ($valor === null) {
                                                        echo '<span style="color: #888; font-style: italic;">NULL</span>';
                                                    } elseif (is_bool($valor)) {
                                                        echo $valor ? 'true' : 'false';
                                                    } else {
                                                        echo htmlspecialchars(strval($valor));
                                                    }
                                                    ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </form>
            
            <!-- Painel Direito - Histórico -->
            <div class="historico-panel">
                <div class="historico-header">
                    <h3><i class="fas fa-history"></i> Histórico de Comandos</h3>
                    <a href="?<?php echo $tabela ? "tabela=" . urlencode($tabela) . "&" : ""; ?>limpar_historico" 
                       class="btn-exemplo" 
                       onclick="return confirm('Limpar todo o histórico?')">
                        <i class="fas fa-trash"></i> Limpar
                    </a>
                </div>
                
                <div class="historico-content">
                    <?php if (empty($_SESSION['sql_historico'])): ?>
                        <div class="historico-vazio">
                            <i class="fas fa-history"></i>
                            <p>Nenhum comando executado ainda</p>
                        </div>
                    <?php else: ?>
                        <ul class="historico-lista">
                            <?php foreach ($_SESSION['sql_historico'] as $index => $item): ?>
                            <li class="historico-item <?php echo $item['sucesso'] ? 'sucesso' : 'erro'; ?>"
                                onclick="carregarDoHistorico(<?php echo $index; ?>)">
                                <div class="historico-sql" title="<?php echo htmlspecialchars($item['sql']); ?>">
                                    <?php echo htmlspecialchars(substr($item['sql'], 0, 100)); ?>
                                    <?php if (strlen($item['sql']) > 100): ?>...<?php endif; ?>
                                </div>
                                <div class="historico-info">
                                    <span class="historico-data">
                                        <i class="far fa-clock"></i> <?php echo $item['data']; ?>
                                    </span>
                                    <span class="historico-status <?php echo $item['sucesso'] ? 'sucesso' : 'erro'; ?>">
                                        <?php if ($item['sucesso']): ?>
                                            <i class="fas fa-check"></i> Sucesso
                                        <?php else: ?>
                                            <i class="fas fa-times"></i> Erro
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php if (!$item['sucesso'] && isset($item['erro'])): ?>
                                <div style="color: #ff9999; font-size: 0.75rem; margin-top: 5px;">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    <?php echo htmlspecialchars(substr($item['erro'], 0, 80)); ?>...
                                </div>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Elementos
    const sqlEditor = document.getElementById('sql-editor');
    const sqlForm = document.getElementById('sql-form');
    
    // Inicializar
    document.addEventListener('DOMContentLoaded', function() {
        // Configurar atalho Ctrl+Enter
        sqlEditor.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                sqlForm.submit();
            }
            
            // Tab com 4 espaços
            if (e.key === 'Tab') {
                e.preventDefault();
                const start = this.selectionStart;
                const end = this.selectionEnd;
                const value = this.value;
                
                this.value = value.substring(0, start) + '    ' + value.substring(end);
                this.selectionStart = this.selectionEnd = start + 4;
            }
        });
        
        // Auto-expandir textarea
        sqlEditor.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        
        // Ajustar altura inicial
        setTimeout(() => {
            sqlEditor.style.height = 'auto';
            sqlEditor.style.height = (sqlEditor.scrollHeight) + 'px';
        }, 100);
    });
    
    // Limpar editor
    function limparEditor() {
        if (confirm('Limpar o editor?')) {
            sqlEditor.value = '';
            sqlEditor.style.height = 'auto';
            sqlEditor.style.height = (sqlEditor.scrollHeight) + 'px';
            sqlEditor.focus();
        }
    }
    
    // Carregar do histórico
    function carregarDoHistorico(index) {
        window.location.href = '?<?php echo $tabela ? "tabela=" . urlencode($tabela) . "&" : ""; ?>carregar=' + index;
    }
    
    // Carregar exemplos
    function carregarExemplo(tipo) {
        const exemplos = {
            'select': `-- Consulta básica
SELECT * FROM usuarios LIMIT 10;

-- Consulta com filtro
SELECT nome, email FROM usuarios WHERE ativo = true;

-- Consulta com ordenação
SELECT * FROM produtos ORDER BY preco DESC LIMIT 5;`,
            
            'create': `-- Criar nova tabela
CREATE TABLE clientes (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE,
    telefone VARCHAR(20),
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Criar tabela com relacionamento
CREATE TABLE pedidos (
    id SERIAL PRIMARY KEY,
    cliente_id INTEGER REFERENCES clientes(id),
    produto VARCHAR(200),
    quantidade INTEGER,
    valor_total DECIMAL(10,2),
    data_pedido DATE DEFAULT CURRENT_DATE
);`,
            
            'insert': `-- Inserir um registro
INSERT INTO usuarios (nome, email) 
VALUES ('Novo Usuário', 'email@exemplo.com');

-- Inserir múltiplos registros
INSERT INTO produtos (nome, preco, categoria) VALUES 
('Produto A', 99.90, 'eletronicos'),
('Produto B', 49.90, 'livros'),
('Produto C', 199.90, 'eletronicos');`,
            
            'update': `-- Atualizar um registro
UPDATE usuarios 
SET nome = 'Nome Atualizado' 
WHERE id = 1;

-- Atualizar múltiplos registros
UPDATE produtos 
SET preco = preco * 0.9 
WHERE estoque > 100;`,
            
            'alter': `-- Adicionar coluna
ALTER TABLE usuarios 
ADD COLUMN telefone VARCHAR(20);

-- Modificar coluna
ALTER TABLE usuarios 
ALTER COLUMN nome TYPE VARCHAR(150);

-- Renomear coluna
ALTER TABLE usuarios 
RENAME COLUMN email TO email_pessoal;`,
            
            'tabelas': `-- Ver todas as tabelas
SELECT table_name as "Nome da Tabela"
FROM information_schema.tables 
WHERE table_schema = 'public' 
AND table_type = 'BASE TABLE'
ORDER BY table_name;

-- Ver tabelas com estatísticas
SELECT 
    t.table_name as "Tabela",
    (SELECT COUNT(*) FROM information_schema.columns c 
     WHERE c.table_name = t.table_name) as "Colunas",
    (xact_commit + xact_rollback) as "Transações"
FROM information_schema.tables t
LEFT JOIN pg_stat_user_tables s ON t.table_name = s.relname
WHERE t.table_schema = 'public'
ORDER BY t.table_name;`
        };
        
        if (exemplos[tipo]) {
            sqlEditor.value = exemplos[tipo];
            sqlEditor.style.height = 'auto';
            sqlEditor.style.height = (sqlEditor.scrollHeight) + 'px';
            sqlEditor.focus();
        }
    }
    
    // Exportar resultados (apenas quando existir)
    function exportarResultados() {
        const tabela = document.querySelector('.tabela-resultados');
        if (!tabela) {
            alert('Não há resultados para exportar.');
            return;
        }
        
        let csv = [];
        const linhas = tabela.querySelectorAll('tr');
        
        linhas.forEach(linha => {
            const colunas = linha.querySelectorAll('td, th');
            const dados = Array.from(colunas).map(col => {
                let texto = col.textContent.trim();
                texto = texto.replace(/"/g, '""');
                if (texto.includes(',') || texto.includes('\n') || texto.includes('"')) {
                    texto = '"' + texto + '"';
                }
                return texto;
            });
            csv.push(dados.join(','));
        });
        
        const csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
        const link = document.createElement('a');
        link.href = encodeURI(csvContent);
        link.download = 'resultados.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    // Função para executar query rápida (para botões extras)
    function executarQuery(query) {
        sqlEditor.value = query;
        sqlForm.submit();
    }
    </script>
</body>
</html>
