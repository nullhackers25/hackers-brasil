document.addEventListener('DOMContentLoaded', function() {
    // Menu Mobile
    const mobileMenuBtn = document.querySelector('.mobile-menu');
    const nav = document.querySelector('nav');
    const searchContainer = document.querySelector('.search-container');
    
    if (mobileMenuBtn && nav && searchContainer) {
        mobileMenuBtn.addEventListener('click', function() {
            // Alterna classe active no nav e no search
            nav.classList.toggle('active');
            searchContainer.classList.toggle('active');
            
            // SEM MUDAR O ÍCONE - apenas o ☺ permanece
            // (você pode adicionar um efeito visual se quiser)
        });
        
        // Fechar ao clicar em um link
        document.querySelectorAll('nav ul li a').forEach(link => {
            link.addEventListener('click', function() {
                nav.classList.remove('active');
                searchContainer.classList.remove('active');
            });
        });
        
        // Fechar ao clicar fora (opcional)
        document.addEventListener('click', function(e) {
            if (!mobileMenuBtn.contains(e.target) && 
                !nav.contains(e.target) && 
                !searchContainer.contains(e.target)) {
                nav.classList.remove('active');
                searchContainer.classList.remove('active');
            }
        });
    }
});
