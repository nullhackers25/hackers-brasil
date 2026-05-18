<?php
session_start();
require_once '../../conexao.php';

// Impedir acesso sem login
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login_admin.php");
    exit;
}

// Função para extrair título da página
function extrairTitulo($arquivo) {
    $conteudo = file_get_contents($arquivo);
    
    // Procura por <title>...</title>
    if (preg_match('/<title>(.*?)<\/title>/is', $conteudo, $matches)) {
        return trim($matches[1]);
    }
    
    // Se não encontrar, retorna o nome do arquivo
    return basename($arquivo);
}

// Função recursiva para listar todos os arquivos PHP e HTML
function listarArquivos($dir, &$resultados = []) {
    $arquivos = scandir($dir);
    
    foreach ($arquivos as $arquivo) {
        if ($arquivo == '.' || $arquivo == '..') continue;
        
        $caminho = $dir . '/' . $arquivo;
        
        if (is_dir($caminho)) {
            // É uma pasta - chama a função novamente (recursão)
            listarArquivos($caminho, $resultados);
        } else {
            // É um arquivo - verifica extensão
            $extensao = pathinfo($arquivo, PATHINFO_EXTENSION);
            if ($extensao == 'php' || $extensao == 'html') {
                $resultados[] = $caminho;
            }
        }
    }
    
    return $resultados;
}

// Diretório base
$public_path = $_SERVER['DOCUMENT_ROOT'] . '/Hackers_Brasil_New/public/';

// Listar todos os arquivos recursivamente
$paginas = listarArquivos($public_path);

// Ordenar por caminho completo
sort($paginas);

// Contar total
$total_paginas = count($paginas);
$total_php = 0;
$total_html = 0;

foreach ($paginas as $p) {
    $ext = pathinfo($p, PATHINFO_EXTENSION);
    if ($ext == 'php') $total_php++;
    if ($ext == 'html') $total_html++;
}

// Processar exclusão via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete' && isset($_POST['arquivo'])) {
        $arquivo = $public_path . $_POST['arquivo']; // Caminho relativo
        if (file_exists($arquivo)) {
            unlink($arquivo);
            echo json_encode(['success' => true, 'message' => 'Página excluída com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Arquivo não encontrado']);
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
    <title>Lista de Páginas</title>
    <link rel="stylesheet" href="../usuarios_bank.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos adicionais */
        .badge-php {
            background: linear-gradient(135deg, #8892BF 0%, #6c7ab0 100%);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            display: inline-block;
        }
        
        .badge-html {
            background: linear-gradient(135deg, #E44D26 0%, #c43c1a 100%);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            display: inline-block;
        }
        
        .caminho {
            font-family: 'Courier New', monospace;
            color: #ffd700;
            font-size: 0.9rem;
        }
        
        .pasta {
            color: #4da3ff;
            font-size: 0.85rem;
            margin-left: 5px;
        }
        
        .filename {
            font-family: 'Courier New', monospace;
            color: #ffd700;
            font-weight: 600;
        }
        
        .acoes {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-visualizar {
            background: linear-gradient(135deg, #4da3ff 0%, #2d8cff 100%);
            color: white;
            border: none;
        }
        
        .btn-editar {
            background: linear-gradient(135deg, #ffaa00 0%, #e69900 100%);
            color: #00101d;
            border: none;
        }
        
        .btn-excluir {
            background: linear-gradient(135deg, #b30000 0%, #8b0000 100%);
            color: white;
            border: none;
        }
        
        .stats-bar {
            background: rgba(13, 17, 23, 0.8);
            border: 1px solid #2a3a5a;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stat-icon {
            color: #4da3ff;
            font-size: 1.5rem;
        }
        
        .stat-info {
            display: flex;
            flex-direction: column;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #4da3ff;
            line-height: 1.2;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #a0b5d4;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        
        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #4da3ff;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 10px 10px 40px;
            background: rgba(0, 0, 0, 0.3);
            border: 2px solid #2a3a5a;
            border-radius: 8px;
            color: #e6f0ff;
            font-size: 0.95rem;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #4da3ff;
        }
        
        .pasta-badge {
            display: inline-block;
            background: rgba(77, 163, 255, 0.1);
            color: #4da3ff;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-left: 8px;
            border: 1px solid rgba(77, 163, 255, 0.3);
        }
        
        @media (max-width: 768px) {
            .acoes {
                flex-direction: column;
            }
            
            .stats-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-box {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1><i class="fas fa-sitemap"></i> Lista de Páginas</h1>

    <div class="painel-acoes">
        <a href="gerenciar_paginas.php" class="btn voltar">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <a href="logout.php" class="btn logout">
            <i class="fas fa-sign-out-alt"></i> Sair
        </a>
    </div>

    <!-- Barra de estatísticas -->
    <div class="stats-bar">
        <div class="stat-item">
            <div class="stat-icon">
                <i class="fas fa-folder-tree"></i>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?= $total_paginas ?></span>
                <span class="stat-label">Total de Páginas</span>
            </div>
        </div>
        
        <div class="stat-item">
            <div class="stat-icon">
                <i class="fas fa-code"></i>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?= $total_php ?></span>
                <span class="stat-label">PHP</span>
            </div>
        </div>
        
        <div class="stat-item">
            <div class="stat-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?= $total_html ?></span>
                <span class="stat-label">HTML</span>
            </div>
        </div>
        
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Buscar por nome ou título..." onkeyup="filterPages()">
        </div>
    </div>

    <div class="table-container">
        <table class="tabela" id="paginasTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Caminho</th>
                    <th>Arquivo</th>
                    <th>Título da Página</th>
                    <th>Tipo</th>
                    <th>Tamanho</th>
                    <th>Modificação</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($paginas)): ?>
                    <?php foreach ($paginas as $index => $arquivo): 
                        // Caminho relativo a partir de 'public/'
                        $caminho_relativo = str_replace($public_path, '', $arquivo);
                        $partes = explode('/', $caminho_relativo);
                        $nome_arquivo = array_pop($partes);
                        $pasta = implode('/', $partes) ?: 'Raiz';
                        
                        $extensao = pathinfo($nome_arquivo, PATHINFO_EXTENSION);
                        $titulo = extrairTitulo($arquivo);
                        $tamanho = filesize($arquivo);
                        $modificado = date('d/m/Y H:i:s', filemtime($arquivo));
                        
                        // Formatar tamanho
                        if ($tamanho < 1024) {
                            $tamanho_format = $tamanho . ' B';
                        } elseif ($tamanho < 1048576) {
                            $tamanho_format = round($tamanho / 1024, 2) . ' KB';
                        } else {
                            $tamanho_format = round($tamanho / 1048576, 2) . ' MB';
                        }
                    ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td>
                            <span class="pasta">
                                <i class="fas fa-folder"></i> <?= htmlspecialchars($pasta) ?>
                            </span>
                        </td>
                        <td><span class="filename"><?= htmlspecialchars($nome_arquivo) ?></span></td>
                        <td><?= htmlspecialchars($titulo) ?></td>
                        <td>
                            <span class="badge-<?= $extensao ?>">
                                <?= strtoupper($extensao) ?>
                            </span>
                        </td>
                        <td><?= $tamanho_format ?></td>
                        <td><?= $modificado ?></td>
                        <td class="acoes">
                            <a href="/Hackers_Brasil/public/<?= $caminho_relativo ?>" target="_blank" class="btn btn-visualizar">
                               <i class="fas fa-eye"></i> Ver
                            </a>
                            <button class="btn btn-editar" onclick="editarPagina('<?= $caminho_relativo ?>')">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-excluir" onclick="excluirPagina('<?= $caminho_relativo ?>', '<?= htmlspecialchars(addslashes($titulo)) ?>')">
                                <i class="fas fa-trash"></i> Excluir
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="no-data">
                            <i class="fas fa-folder-open"></i>
                            <p>Nenhuma página encontrada</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de Confirmação -->
<div id="confirmModal" class="modal">
    <div class="modal-content confirm-modal">
        <div class="modal-header">
            <h2><i class="fas fa-exclamation-triangle"></i> Confirmar Exclusão</h2>
            <button class="close-modal" onclick="closeConfirmModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="confirmMessage">Tem certeza que deseja excluir esta página?</p>
            <div class="form-footer">
                <button class="btn cancelar" onclick="closeConfirmModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button class="btn excluir-confirm" onclick="confirmDelete()">
                    <i class="fas fa-trash"></i> Sim, Excluir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast de Notificação -->
<div id="toast" class="toast"></div>

<script>
let currentFile = null;

// Função de busca
function filterPages() {
    let input = document.getElementById('searchInput');
    let filter = input.value.toLowerCase();
    let table = document.getElementById('paginasTable');
    let rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        let row = rows[i];
        let pasta = row.getElementsByTagName('td')[1]?.textContent.toLowerCase();
        let arquivo = row.getElementsByTagName('td')[2]?.textContent.toLowerCase();
        let titulo = row.getElementsByTagName('td')[3]?.textContent.toLowerCase();
        
        if (pasta && arquivo && titulo) {
            if (pasta.includes(filter) || arquivo.includes(filter) || titulo.includes(filter)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    }
}

function editarPagina(arquivo) {
    showToast('Funcionalidade de edição em breve!', 'info');
}

function excluirPagina(arquivo, titulo) {
    currentFile = arquivo;
    document.getElementById('confirmMessage').innerHTML = 
        `Tem certeza que deseja <strong>excluir</strong> a página <strong>"${titulo}"</strong>?<br>
         <small>Arquivo: ${arquivo}</small>`;
    document.getElementById('confirmModal').style.display = 'flex';
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
    currentFile = null;
}

function confirmDelete() {
    if (!currentFile) return;
    
    fetch('lista_paginas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=delete&arquivo=' + encodeURIComponent(currentFile)
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
        showToast('Erro ao excluir página', 'error');
        console.error('Error:', error);
        closeConfirmModal();
    });
}

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
    const modal = document.getElementById('confirmModal');
    if (event.target === modal) {
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

</body>
</html>
