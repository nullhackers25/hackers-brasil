// Função Universal Abrir e Fechar Botões //
function toggleConteudo(botaoId, conteudoId) {
    var conteudo = document.getElementById(conteudoId);
    var botao = document.getElementById(botaoId);
    
    if (conteudo.style.display === 'none' || conteudo.style.display === '') {
        conteudo.style.display = 'block';
        botao.innerHTML = '<i class="fa-solid fa-eye-slash"></i> <strong>Ocultar Conteudo</strong>';
        botao.title = "Clique para ocultar";
    } else {
        conteudo.style.display = 'none';
        botao.innerHTML = '<i class="fa-solid fa-eye"></i> <strong>Mostrar Conteudo</strong>';
        botao.title = "Clique para mostrar";
    }
}

// ==================================================, 
// CARREGAR E INJETAR SNIPPETS DE CÓDIGO (via JSON)
// ==================================================

// Cache dos snippets após carregar
let snippetsCache = {};
let snippetsSensivelCache = {};

// Função para buscar o JSON e injetar os códigos
async function loadSnippetsAndInject() {
    try {
        // Carrega SNIPPETS NORMAIS (se houver elementos com data-snippet)
        if (document.querySelectorAll('code[data-snippet]').length > 0) {
            const response = await fetch('/Hackers_Brasil_New/public/assets/js/snippets.json');
            if (!response.ok) throw new Error('Erro ao carregar snippets.json');
            snippetsCache = await response.json();
            
            // Injeta nos elementos data-snippet
            document.querySelectorAll('code[data-snippet]').forEach(el => {
                const key = el.getAttribute('data-snippet');
                if (snippetsCache.hasOwnProperty(key)) {
                    el.textContent = snippetsCache[key];
                } else {
                    console.warn(`Snippet não encontrado: ${key}`);
                    el.textContent = '// Código indisponível';
                }
            });
        }
        
        // Carrega SNIPPETS SENSÍVEIS (se houver elementos com data-snippet-sensivel)
        if (document.querySelectorAll('code[data-snippet-sensivel]').length > 0) {
            const responseSensivel = await fetch('/Hackers_Brasil_New/public/assets/js/snippets-sensivel.json');
            if (!responseSensivel.ok) throw new Error('Erro ao carregar snippets-sensivel.json');
            snippetsSensivelCache = await responseSensivel.json();
            
            // Injeta nos elementos data-snippet-sensivel
            document.querySelectorAll('code[data-snippet-sensivel]').forEach(el => {
                const key = el.getAttribute('data-snippet-sensivel');
                if (snippetsSensivelCache.hasOwnProperty(key)) {
                    el.textContent = snippetsSensivelCache[key];
                } else {
                    console.warn(`Snippet sensível não encontrado: ${key}`);
                    el.textContent = '// Código indisponível';
                }
            });
        }
        
    } catch (error) {
        console.error('Falha ao carregar snippets:', error);
        document.querySelectorAll('code[data-snippet], code[data-snippet-sensivel]').forEach(el => {
            el.textContent = '// Erro ao carregar código';
        });
    }
}

// Executar quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadSnippetsAndInject);
} else {
    loadSnippetsAndInject();
}
