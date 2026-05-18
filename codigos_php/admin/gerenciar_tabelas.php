<?php
session_start();
require_once '../conexao.php';

// Verificar login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Buscar todas as tabelas do banco
try {
    $sql = "SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_type = 'BASE TABLE' 
            ORDER BY table_name";
    
    $stmt = $conn->query($sql);
    $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Estatísticas
    $total_tabelas = count($tabelas);
    $tabelas_info = [];
    
    // Buscar informações de cada tabela
    foreach ($tabelas as $tabela) {
        $sql_colunas = "SELECT COUNT(*) 
                       FROM information_schema.columns 
                       WHERE table_name = :tabela";
        $stmt_colunas = $conn->prepare($sql_colunas);
        $stmt_colunas->bindParam(':tabela', $tabela);
        $stmt_colunas->execute();
        $total_colunas = $stmt_colunas->fetchColumn();
        
        $sql_registros = "SELECT COUNT(*) FROM \"$tabela\"";
        $stmt_registros = $conn->query($sql_registros);
        $total_registros = $stmt_registros->fetchColumn();
        
        $tabelas_info[$tabela] = [
            'colunas' => $total_colunas,
            'registros' => $total_registros
        ];
    }
    
} catch (PDOException $e) {
    $erro = "Erro ao buscar tabelas: " . $e->getMessage();
    $tabelas = [];
    $tabelas_info = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Tabelas</title>
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
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    /* Header */
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #2a3a5a;
    }
    
    .header h1 {
        font-size: 2rem;
        background: linear-gradient(135deg, #4da3ff 0%, #2d8cff 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
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
    
    .btn.voltar {
        background: linear-gradient(135deg, #1b263b 0%, #24344d 100%);
        color: #dceaff;
        border: 1px solid #2a3a5a;
    }
    
    .btn.logout {
        background: linear-gradient(135deg, #ff4d4d 0%, #cc0000 100%);
        color: white;
        border: 1px solid #ff6666;
    }
    
    .btn:hover {
        transform: translateY(-2px);
    }
    
    /* Informações */
    .info-section {
        background: linear-gradient(135deg, #1b263b 0%, #24344d 100%);
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        border: 1px solid #2a3a5a;
    }
    
    .info-section h2 {
        color: #e6f0ff;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .info-section p {
        color: #a0b3d6;
        line-height: 1.6;
        margin-bottom: 15px;
    }
    
    .info-section ul {
        color: #a0b3d6;
        margin-left: 20px;
        margin-bottom: 15px;
    }
    
    .info-section li {
        margin-bottom: 8px;
    }
    
    .aviso {
        background: rgba(255, 193, 7, 0.1);
        border: 1px solid #ffc107;
        border-radius: 10px;
        padding: 20px;
        margin-top: 20px;
        display: flex;
        align-items: flex-start;
        gap: 15px;
    }
    
    .aviso i {
        color: #ffc107;
        font-size: 1.5rem;
        margin-top: 3px;
    }
    
    .aviso p {
        margin: 0;
        color: #ffd966;
    }
    
    /* Cards de Ação */
    .actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }
    
    .action-card {
        background: #1a2029;
        border-radius: 15px;
        padding: 30px;
        border: 1px solid #2a3a5a;
        transition: all 0.3s;
        text-decoration: none;
        display: block;
        color: inherit;
    }
    
    .action-card:hover {
        transform: translateY(-5px);
        border-color: #4da3ff;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
    }
    
    .card-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .card-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
    }
    
    .card-icon.editar {
        background: linear-gradient(135deg, #4da3ff 0%, #2d8cff 100%);
        color: white;
    }
    
    .card-icon.criar {
        background: linear-gradient(135deg, #00cc66 0%, #00994d 100%);
        color: white;
    }
    
    .card-icon.sql {
        background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
        color: white;
    }
    
    .card-icon.backup {
        background: linear-gradient(135deg, #ff9900 0%, #cc6600 100%);
        color: white;
    }
    
    .card-title {
        font-size: 1.4rem;
        color: #e6f0ff;
        margin-bottom: 5px;
    }
    
    .card-desc {
        color: #a0b3d6;
        font-size: 0.95rem;
        margin-bottom: 20px;
        line-height: 1.5;
    }
    
    .card-features {
        list-style: none;
        padding: 0;
    }
    
    .card-features li {
        color: #a0b3d6;
        margin-bottom: 8px;
        padding-left: 25px;
        position: relative;
        font-size: 0.9rem;
    }
    
    .card-features li:before {
        content: '✓';
        position: absolute;
        left: 0;
        color: #4da3ff;
        font-weight: bold;
    }
    
    /* Lista de Tabelas */
    .tabelas-section {
        background: #1a2029;
        border-radius: 15px;
        padding: 30px;
        border: 1px solid #2a3a5a;
    }
    
    .tabelas-section h2 {
        color: #e6f0ff;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .tabelas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 15px;
    }
    
    .tabela-card {
        background: linear-gradient(135deg, #1b263b 0%, #24344d 100%);
        border-radius: 10px;
        padding: 20px;
        border: 1px solid #2a3a5a;
        transition: all 0.3s;
        text-decoration: none;
        color: inherit;
        display: block;
    }
    
    .tabela-card:hover {
        border-color: #4da3ff;
        transform: translateY(-3px);
    }
    
    .tabela-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }
    
    .tabela-nome {
        font-weight: 600;
        color: #e6f0ff;
        font-size: 1.1rem;
        word-break: break-all;
    }
    
    .tabela-stats {
        display: flex;
        gap: 10px;
        font-size: 0.85rem;
    }
    
    .stat {
        background: rgba(255, 255, 255, 0.05);
        padding: 4px 10px;
        border-radius: 12px;
        color: #a0b3d6;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .tabela-info {
        color: #a0b3d6;
        font-size: 0.9rem;
        line-height: 1.4;
    }
    
    .tabela-acoes {
        display: flex;
        gap: 8px;
        margin-top: 15px;
    }
    
    .btn-acao {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.85rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        flex: 1;
        justify-content: center;
    }
    
    .btn-acao.editar {
        background: linear-gradient(135deg, #4da3ff 0%, #2d8cff 100%);
        color: white;
    }
    
    .btn-acao.visualizar {
        background: linear-gradient(135deg, #00cc66 0%, #00994d 100%);
        color: white;
    }
    
    .btn-acao.sql {
        background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
        color: white;
    }
    
    /* Estatísticas */
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
        border-color: #4da3ff;
    }
    
    .stat-icon {
        font-size: 2.5rem;
        margin-bottom: 10px;
        color: #4da3ff;
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        margin: 10px 0;
        color: #e6f0ff;
    }
    
    .stat-label {
        color: #a0b3d6;
        font-size: 0.9rem;
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
        padding: 60px 20px;
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
        margin-bottom: 20px;
    }
   </style>
</head>
<body>
	
	<div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-database"></i> Gerenciar Banco de Dados</h1>
            <div class="nav-buttons">
                <a href="admin.php" class="btn voltar">
                    <i class="fas fa-arrow-left"></i> Voltar ao Painel
                </a>
                <a href="logout.php" class="btn logout">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
        
        <?php if (isset($erro)): ?>
        <div class="mensagem-erro">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($erro); ?>
        </div>
        <?php endif; ?>
        
        <!-- Estatísticas -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-table"></i>
                </div>
                <div class="stat-number"><?php echo $total_tabelas; ?></div>
                <div class="stat-label">Tabelas no Sistema</div>
            </div>
            
            <?php 
            // Calcular total de colunas
            $total_colunas = 0;
            foreach ($tabelas_info as $info) {
                $total_colunas += $info['colunas'];
            }
            ?>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-columns"></i>
                </div>
                <div class="stat-number"><?php echo $total_colunas; ?></div>
                <div class="stat-label">Total de Colunas</div>
            </div>
            
            <?php 
            // Calcular total de registros
            $total_registros = 0;
            foreach ($tabelas_info as $info) {
                $total_registros += $info['registros'];
            }
            ?>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-database"></i>
                </div>
                <div class="stat-number"><?php echo number_format($total_registros); ?></div>
                <div class="stat-label">Registros no Banco</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-server"></i>
                </div>
                <div class="stat-number">NeonDB</div>
                <div class="stat-label">Sistema de Banco</div>
            </div>
        </div>
        
        <!-- Informações -->
        <div class="info-section">
            <h2><i class="fas fa-info-circle"></i> Sobre o Gerenciador de Tabelas</h2>
            <p>Este sistema permite gerenciar completamente a estrutura do banco de dados PostgreSQL. Você pode visualizar, criar, editar e excluir tabelas e colunas através de uma interface intuitiva.</p>
            
            <p><strong>Funcionalidades disponíveis:</strong></p>
            <ul>
                <li><strong>Visualizar Tabelas:</strong> Veja todas as tabelas do sistema com estatísticas</li>
                <li><strong>Editar Tabelas:</strong> Modifique estruturas existentes (adicionar/remover colunas)</li>
                <li><strong>Criar Tabelas:</strong> Crie novas tabelas com interface visual</li>
                <li><strong>Editor SQL Avançado:</strong> Execute comandos SQL diretamente</li>
                <li><strong>Backup e Restauração:</strong> Gerencie cópias de segurança</li>
            </ul>
            
            <div class="aviso">
                <i class="fas fa-exclamation-triangle"></i>
                <p><strong>Atenção:</strong> Alterações na estrutura do banco de dados podem afetar o funcionamento do sistema. Certifique-se de entender as consequências antes de realizar modificações.</p>
            </div>
        </div>
        
       <!-- Cards de Ação (SEM LINKS - APENAS INFORMATIVOS) -->
<div class="actions-grid">
    <div class="action-card">
        <div class="card-header">
            <div class="card-icon editar">
                <i class="fas fa-edit"></i>
            </div>
            <div>
                <h3 class="card-title">Editar Tabelas</h3>
                <p class="card-desc">Modificar estrutura de tabelas existentes</p>
            </div>
        </div>
        <ul class="card-features">
            <li>Adicionar novas colunas</li>
            <li>Renomear colunas existentes</li>
            <li>Remover colunas (com confirmação)</li>
            <li>Alterar tipos de dados</li>
            <li>Ver estrutura completa</li>
        </ul>
    </div>
    
    <div class="action-card">
        <div class="card-header">
            <div class="card-icon criar">
                <i class="fas fa-plus-circle"></i>
            </div>
            <div>
                <h3 class="card-title">Criar Nova Tabela</h3>
                <p class="card-desc">Criar tabelas do zero com interface visual</p>
            </div>
        </div>
        <ul class="card-features">
            <li>Interface drag-and-drop</li>
            <li>Todos os tipos de dados PostgreSQL</li>
            <li>Definir chaves primárias</li>
            <li>Adicionar constraints</li>
            <li>Índices automáticos</li>
        </ul>
    </div>
    
    <div class="action-card">
        <div class="card-header">
            <div class="card-icon sql">
                <i class="fas fa-code"></i>
            </div>
            <div>
                <h3 class="card-title">Editor SQL Avançado</h3>
                <p class="card-desc">Execute comandos SQL diretamente</p>
            </div>
        </div>
        <ul class="card-features">
            <li>Sintax highlighting</li>
            <li>Auto-complete</li>
            <li>Histórico de consultas</li>
            <li>Exportar resultados</li>
            <li>Modo sandbox seguro</li>
        </ul>
    </div>
    
    <div class="action-card">
        <div class="card-header">
            <div class="card-icon backup">
                <i class="fas fa-save"></i>
            </div>
            <div>
                <h3 class="card-title">Backup & Restauração</h3>
                <p class="card-desc">Gerencie cópias de segurança</p>
            </div>
        </div>
        <ul class="card-features">
            <li>Backup completo do banco</li>
            <li>Backup de tabelas específicas</li>
            <li>Agendar backups automáticos</li>
            <li>Restaurar de backup</li>
            <li>Download de arquivos SQL</li>
        </ul>
    </div>
</div>
        
    <div class="tabelas-grid">
    <?php 
    // Mapeamento exato de tabelas para suas páginas de EDIÇÃO
    $mapeamento_edicao = [
        'admin_usuarios' => 'editar_tabela_admin_usuarios.php',
        'bloqueios_usuarios' => 'editar_tabela_bloqueios_usuarios.php',
        'logins_bloqueados' => 'editar_tabela_logins_bloqueados.php',
        'tentativas_cadastro' => 'editar_tabela_tentativas_cadastro.php',
        'tentativas_login' => 'editar_tabela_tentativas_login.php',
        'usuarios' => 'editar_tabela_usuarios.php',
        'usuarios_online' => 'editar_tabela_usuarios_online.php',
        'usuarios_pendentes' => 'editar_tabela_usuarios_pendentes.php'
    ];
    
    // Mapeamento exato de tabelas para suas páginas de VISUALIZAÇÃO
    $mapeamento_visualizacao = [
        'admin_usuarios' => 'administradores.php',
        'usuarios' => 'usuarios_bank.php',
        'usuarios_online' => 'usuarios_online.php',
        'usuarios_pendentes' => 'usuarios_pendentes_bank.php',
        'bloqueios_usuarios' => 'visualizar_tabela.php', // Página padrão até criar específica
        'logins_bloqueados' => 'visualizar_tabela.php',  // Página padrão até criar específica
        'tentativas_cadastro' => 'visualizar_tabela.php', // Página padrão até criar específica
        'tentativas_login' => 'visualizar_tabela.php'    // Página padrão até criar específica
    ];
    
    foreach ($tabelas as $tabela): 
        $info = $tabelas_info[$tabela] ?? ['colunas' => 0, 'registros' => 0];
        
        // Verificar se a tabela está no mapeamento de EDIÇÃO
        $pagina_edicao = isset($mapeamento_edicao[$tabela]) 
            ? $mapeamento_edicao[$tabela] 
            : "editar_tabela.php"; // Padrão
        
        // Verificar se a tabela está no mapeamento de VISUALIZAÇÃO
        $pagina_visualizacao = isset($mapeamento_visualizacao[$tabela]) 
            ? $mapeamento_visualizacao[$tabela] 
            : "visualizar_tabela.php"; // Padrão
    ?>
    <div class="tabela-card">
        <div class="tabela-header">
            <div class="tabela-nome"><?php echo htmlspecialchars($tabela); ?></div>
            <div class="tabela-stats">
                <span class="stat" title="Colunas">
                    <i class="fas fa-columns"></i> <?php echo $info['colunas']; ?>
                </span>
                <span class="stat" title="Registros">
                    <i class="fas fa-list"></i> <?php echo number_format($info['registros']); ?>
                </span>
            </div>
        </div>
        <div class="tabela-info">
            Tabela com <?php echo $info['colunas']; ?> coluna(s) e <?php echo number_format($info['registros']); ?> registro(s)
        </div>
        <div class="tabela-acoes">
            <a href="<?php echo $pagina_edicao; ?>?tabela=<?php echo urlencode($tabela); ?>" class="btn-acao editar">
                <i class="fas fa-edit"></i> Editar
            </a>
            <a href="<?php echo $pagina_visualizacao; ?>?tabela=<?php echo urlencode($tabela); ?>" class="btn-acao visualizar">
                <i class="fas fa-eye"></i> Ver Dados
            </a>
            <a href="editor_sql.php?tabela=<?php echo urlencode($tabela); ?>" class="btn-acao sql">
                <i class="fas fa-code"></i> SQL
            </a>  
        </div>
    </div>
    <?php endforeach; ?>
</div>
    </div>
    
 </body>
</html>
