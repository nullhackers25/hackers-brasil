// search.js
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const suggestionsBox = document.getElementById('suggestions');
    const searchForm = document.getElementById('searchForm');

    let searchIndex = {};        // { "termo": "url", ... }
    let history = [];            // últimos 15 termos pesquisados

    // Carregar índice de busca
    async function loadSearchIndex() {
        try {
            const response = await fetch('/Hackers_Brasil/public/assets/js/search-index.json');
            if (!response.ok) throw new Error('Erro ao carregar índice');
            searchIndex = await response.json();
            console.log('Índice carregado:', searchIndex);
        } catch (error) {
            console.error('Falha ao carregar índice de busca:', error);
        }
    }

    // Carregar histórico do localStorage
    function loadHistory() {
        const saved = localStorage.getItem('searchHistory');
        if (saved) {
            try {
                history = JSON.parse(saved);
                if (!Array.isArray(history)) history = [];
                if (history.length > 15) history = history.slice(0, 15);
            } catch (e) {
                history = [];
            }
        }
    }

    // Salvar histórico no localStorage
    function saveHistory() {
        localStorage.setItem('searchHistory', JSON.stringify(history.slice(0, 15)));
    }

    // Adicionar termo ao histórico
    function addToHistory(term) {
        term = term.trim().toLowerCase();
        if (!term) return;
        history = history.filter(t => t !== term);
        history.unshift(term);
        if (history.length > 15) history.pop();
        saveHistory();
    }

    // Exibir sugestões
    function showSuggestions(filter = '') {
        let list = [];
        if (filter) {
            const lowerFilter = filter.toLowerCase();
            list = Object.keys(searchIndex)
                .filter(key => key.toLowerCase().startsWith(lowerFilter))
                .slice(0, 10);
        } else {
            list = history.slice(0, 5);
        }

        suggestionsBox.innerHTML = '';
        if (list.length === 0) {
            suggestionsBox.style.display = 'none';
            return;
        }

        list.forEach(term => {
            const div = document.createElement('div');
            div.textContent = term;
            div.addEventListener('click', () => {
                searchInput.value = term;
                performSearch(term);
                suggestionsBox.style.display = 'none';
            });
            suggestionsBox.appendChild(div);
        });
        suggestionsBox.style.display = 'block';
    }

    // Executar a busca
    function performSearch(term) {
        term = term.trim().toLowerCase();
        if (!term) return;

        const url = searchIndex[term];
        if (url) {
            addToHistory(term);
            window.location.href = url;
        } else {
            alert('Nenhuma página encontrada para "' + term + '".');
        }
    }

    // Clique no botão de busca
    searchBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const term = searchInput.value.trim();
        if (term) performSearch(term);
    });

    // Input no campo de busca
    searchInput.addEventListener('input', function() {
        const val = this.value.trim();
        if (val.length >= 2) {
            showSuggestions(val);
        } else {
            suggestionsBox.style.display = 'none';
        }
    });

    // Foco no campo
    searchInput.addEventListener('focus', function() {
        if (this.value.trim() === '') {
            showSuggestions('');
        }
    });

    // Fechar sugestões ao clicar fora
    document.addEventListener('click', function(e) {
        if (!searchForm.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.style.display = 'none';
        }
    });

    // Submeter formulário
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const term = searchInput.value.trim();
        if (term) performSearch(term);
    });

    // Inicializar
    loadSearchIndex();
    loadHistory();
});
