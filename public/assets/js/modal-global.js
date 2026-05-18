(function() {
        // Abrir modal ao clicar em qualquer link com a classe "abrir-modal"
        document.querySelectorAll('.abrir-modal').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const modalId = this.dataset.modal; // pega o valor de data-modal
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'block';
                    document.body.style.overflow = 'hidden'; // trava rolagem
                }
            });
        });

        // Fechar modal ao clicar no "x" (botão com classe "close" e atributo data-fechar)
        document.querySelectorAll('.close[data-fechar]').forEach(botao => {
            botao.addEventListener('click', function() {
                const modalId = this.dataset.fechar;
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = ''; // destrava rolagem
                }
            });
        });

        // Fechar modal ao clicar fora da área de conteúdo (fundo escuro)
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-global')) {
                // e.target é o fundo do modal (div com classe modal-global)
                e.target.style.display = 'none';
                document.body.style.overflow = '';
            }
        });

        // (Opcional) Fechar com tecla ESC
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modalAberto = document.querySelector('.modal-global[style*="display: block"]');
                if (modalAberto) {
                    modalAberto.style.display = 'none';
                    document.body.style.overflow = '';
                }
            }
        });
    })();
