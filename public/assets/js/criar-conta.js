// ==========================================
// ======= EXECUTA QUANDO A PÁGINA CARREGA ===
// ==========================================
document.addEventListener('DOMContentLoaded', function() {

    // ======= MOSTRAR/OCULTAR SENHA =======
    const toggleSenha = document.getElementById('toggleSenha');
    const toggleConfirmarSenha = document.getElementById('toggleConfirmarSenha');
    const senha = document.getElementById('senha');
    const confirmarSenha = document.getElementById('confirmar_senha');

    function togglePassword(input, button) {
        const type = input.type === 'password' ? 'text' : 'password';
        input.type = type;
        button.innerHTML = type === 'password'
            ? '<i class="fas fa-eye"></i>'
            : '<i class="fas fa-eye-slash"></i>';
    }

    if(toggleSenha) toggleSenha.addEventListener('click', () => togglePassword(senha, toggleSenha));
    if(toggleConfirmarSenha) toggleConfirmarSenha.addEventListener('click', () => togglePassword(confirmarSenha, toggleConfirmarSenha));

    // ======= CHECKLIST SENHA =======
    const charCount = document.getElementById('charCount');
    const len = document.getElementById('len');
    const upper = document.getElementById('upper');
    const lower = document.getElementById('lower');
    const number = document.getElementById('number');
    const special = document.getElementById('special');

    if(senha){
        senha.addEventListener('input', function() {
            const val = senha.value;
            const specials = /[@#*&%!+$,_]/;

            const hasUpper = /[A-Z]/.test(val);
            const hasLower = /[a-z]/.test(val);
            const hasNumber = /[0-9]/.test(val);
            const hasSpecial = specials.test(val);
            const isLen = val.length >= 8;

            if(charCount) charCount.textContent = val.length;

            if(len) len.textContent = (isLen ? '✅' : '❌') + ` Mínimo 8 caracteres (${val.length})`;
            if(upper) upper.textContent = (hasUpper ? '✅' : '❌') + ' 1 letra maiúscula';
            if(lower) lower.textContent = (hasLower ? '✅' : '❌') + ' 1 letra minúscula';
            if(number) number.textContent = (hasNumber ? '✅' : '❌') + ' 1 número';
            if(special) special.textContent = (hasSpecial ? '✅' : '❌') + ' 1 caractere especial (@ # * & % ! + $ _)';
        });
    }

    // ======= CONFIRMAÇÃO DE SENHA =======
    if(confirmarSenha){
        confirmarSenha.addEventListener('input', function() {
            confirmarSenha.setCustomValidity(
                confirmarSenha.value !== senha.value ? "As senhas não coincidem" : ""
            );
        });
    }

    // ======= VALIDAÇÃO DO NOME COMPLETO =======
    const nome = document.getElementById('nome_completo');
    if(nome){
        nome.addEventListener('blur', function() {
            const words = nome.value.trim().split(/\s+/);
            if (words.length < 2) {
                alert('Insira seu nome completo.');
                nome.focus();
            }
        });
    }

    // ======= VALIDAÇÃO DO E-MAIL =======
    const email = document.getElementById('email');
    if(email){
        email.addEventListener('blur', function() {
            const emailRegex = /^[^\s@]+@[a-zA-Z0-9.-]+\.(com|org|net|br|gov|edu)(\.(br))?$/;
            if (!emailRegex.test(email.value.trim())) {
                alert('Digite um e-mail válido com domínio correto (.com, .org, .net, .br, etc.)');
                email.focus();
            }
        });
    }

    // ======= ENVIO DO FORMULÁRIO DE CADASTRO =======
    const formRegister = document.getElementById('formRegister');
    if(formRegister){
        formRegister.addEventListener('submit', function(event) {
            event.preventDefault();

            // Confirma checklist da senha antes de enviar
            const val = senha.value;
            if (
                val.length < 8 ||
                !/[A-Z]/.test(val) ||
                !/[a-z]/.test(val) ||
                !/[0-9]/.test(val) ||
                !/[@#*&%!+$,_]/.test(val)
            ) {
                alert('Sua senha não atende todos os requisitos.');
                return false;
            }

            const formData = new FormData(this);

            fetch("../codigos_php/criar-conta.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.text())
            .then(data => {
                if (data.includes("sucesso")) {
                    document.getElementById("emailToken").value = email.value;
                    document.getElementById("emailTokenDisplay").textContent = email.value;
                    document.getElementById('modalToken').style.display = 'flex';
                } else {
                    alert(data);
                }
            })
            .catch(err => alert("Erro ao enviar requisição: " + err));
        });
    }

    // ======= ENVIO DO FORMULÁRIO DE TOKEN =======
    const formToken = document.getElementById('formToken');
    if(formToken){
        formToken.addEventListener('submit', function(e){
            e.preventDefault();

            const email = document.getElementById('emailToken').value;
            const token = document.getElementById('codigoToken').value;

            fetch("../codigos_php/verificar_token.php", {
                method: "POST",
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `email=${encodeURIComponent(email)}&token=${encodeURIComponent(token)}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'sucesso'){
                    alert(data.mensagem);
                    document.getElementById('modalToken').style.display = 'none';
                    window.location.href = '../public/index.html'; // redireciona para página inicial
                } else {
                    alert(data.mensagem);
                }
            })
            .catch(err => alert('Erro ao verificar token: ' + err));
        });
    }

   // ======= REENVIAR TOKEN =======
const formReenviarToken = document.getElementById('formReenviarToken');
if(formReenviarToken){
    formReenviarToken.addEventListener('submit', function(e){
        e.preventDefault();

        const emailReenvio = document.getElementById('emailReenviarToken').value;

        if(!emailReenvio){
            alert('Digite seu e-mail para reenviar o token.');
            return;
        }

        fetch("../codigos_php/reenviar_token_cadastro.php", {
            method: "POST",
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `email=${encodeURIComponent(emailReenvio)}`
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'sucesso'){
                alert(data.mensagem);

                // Fecha modal de reenviar token
                document.getElementById('modalReenviarToken').style.display = 'none';

                // Abre modal do token existente
                document.getElementById("emailToken").value = emailReenvio;
                document.getElementById("emailTokenDisplay").textContent = emailReenvio;
                document.getElementById('modalToken').style.display = 'flex';
            } else {
                alert(data.mensagem);
            }
        })
        .catch(err => alert('Erro ao reenviar token: ' + err));
    });
}

// ======= BOTÃO "REENVIAR" NO MODAL TOKEN =======
const btnAbrirReenviar = document.getElementById('abrirReenviarToken');
if(btnAbrirReenviar){
    btnAbrirReenviar.addEventListener('click', function(){
        // Fecha o modal de token
        document.getElementById('modalToken').style.display = 'none';

        // Abre o modal de reenviar token
        document.getElementById('modalReenviarToken').style.display = 'flex';
    });
}


// ======= BOTÃO DE FECHAR DO MODAL REENVIAR TOKEN =======
const closeReenviarToken = document.getElementById('closeReenviarToken');
if(closeReenviarToken){
    closeReenviarToken.addEventListener('click', function(){
        document.getElementById('modalReenviarToken').style.display = 'none';
    });
}

});
