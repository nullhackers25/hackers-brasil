// ==========================================
// ======= LOGIN.HTML - JS SIMPLIFICADO =====
// ======= SOMENTE CAPTCHA + LOGIN ==========
// ==========================================

document.addEventListener("DOMContentLoaded", function () {

    // ======= ELEMENTOS =======
    const formLogin = document.getElementById("formLogin");
    const usuarioEl = document.getElementById("usuario");
    const senhaEl = document.getElementById("senha");
    const toggleSenhaBtn = document.getElementById("togglePassword");
    
    // ======= TOGGLE SENHA =======
    toggleSenhaBtn.addEventListener("click", () => {
        const type = senhaEl.type === "password" ? "text" : "password";
        senhaEl.type = type;
        toggleSenhaBtn.innerHTML =
            type === "password"
                ? "<i class='fas fa-eye'></i>"
                : "<i class='fas fa-eye-slash'></i>";
    });

    // ======= ENVIO FORM LOGIN =======
    formLogin.addEventListener("submit", function (e) {
        e.preventDefault();
        
        const formData = new FormData(formLogin);

        fetch("../codigos_php/login.php", {
            method: "POST",
            body: formData
        })
            .then(r => r.text())
            .then(data => {
                if (data.includes("sucesso")) {
                    window.location.href = "painel/Bem_Vindo_Ciberguranca.php";
                } else {
                    alert(data);
                }
            })
            .catch(err => {
                console.error(err);
                alert("Erro ao conectar com o servidor.");
            });
    });

});
