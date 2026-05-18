<?php
require_once '../codigos_php/config.php';  // ou o caminho correto
$site_key = TURNSTILE_SITE_KEY;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Hackers Brasil - Login</title>
<link rel="stylesheet" href="assets/css/login.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>

<div class="login-container" id="loginWrapper">
    <div class="login-header">
        <h1><i class="fas fa-user-secret"></i> Hackers Brasil</h1>
        <p>Acesse sua conta</p>
    </div>

    <!-- FORMULÁRIO DE LOGIN (sempre renderizado) -->
    <form class="login-form" id="formLogin" method="POST" action="../codigos_php/login.php" onsubmit="return validarCaptcha();">
        <div class="form-group">
            <label for="usuario"><i class="fas fa-user"></i> Usuário ou E-mail</label>
            <input type="text" id="usuario" name="usuario" required autocomplete="username" />
        </div>

        <input type="hidden" id="login_provider" name="login_provider" value="local">

        <div class="form-group">
            <label for="senha"><i class="fas fa-lock"></i> Senha</label>
            <input type="password" id="senha" name="senha" required autocomplete="current-password" />
            <button type="button" class="password-toggle" id="togglePassword" aria-label="Mostrar senha">
                <i class="fas fa-eye"></i>
            </button>
        </div>

        <!-- Cloudflare Turnstile -->
        <div class="form-group" style="margin-bottom:14px;">
            <div class="cf-turnstile" data-sitekey="<?php echo TURNSTILE_SITE_KEY; ?>"></div>
        </div>

        <div class="remember-me">
            <input type="checkbox" id="manterLogado" name="manterLogado" />
            <label for="manterLogado" style="color:var(--text-light)">Manter conectado</label>
        </div>

        <button type="submit" class="login-btn">ACESSAR</button>
        
        <button type="button" class="google-btn" id="googleLoginBtn">
               <i class="fab fa-google"></i> Entrar com Google
        </button>

        <div class="login-links">
            <a href="/forgot-password">Esqueceu a senha?</a>
            <a href="criar-conta.html">Não tem conta? Criar uma</a>
        </div>
    </form>
</div>

<!-- MODAL DO TOKEN (sempre presente mas oculto) -->
<div class="modal-overlay" id="modalToken">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <span class="close-x" id="closeModalToken" title="Fechar modal"><i class="fas fa-times"></i></span>

        <div class="modal-title">
            <h2 id="modalTitle"><i class="fas fa-user-secret"></i> Hackers Brasil</h2>
            <p>Digite o token de 6 dígitos enviado para seu e-mail</p>
        </div>

        <form id="formToken" method="POST" action="verificar_token_login.php">
            <!-- hidden field to keep the user/email for token verification -->
            <input type="hidden" id="emailToken" name="usuario" value="">

            <div class="token-inputs" id="tokenInputs">
                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="token-box" />
                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="token-box" />
                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="token-box" />
                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="token-box" />
                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="token-box" />
                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="token-box" />
            </div>

            <div class="token-actions">
                <button type="submit" class="btn-confirm" id="confirmTokenBtn">Confirmar Token</button>
                <button type="button" class="btn-resend" id="resendTokenBtn">Reenviar Token</button>
            </div>
            <p class="hint" id="tokenHint">Aguarde o e-mail com o código — válido por alguns minutos.</p>
        </form>
    </div>
</div>

<!-- MODAL REENVIAR TOKEN -->
<div class="modal-overlay" id="modalReenviarToken">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalReenviarTitle">
        <span class="close-x" id="closeReenviarToken" title="Fechar modal"><i class="fas fa-times"></i></span>

        <div class="modal-title">
            <h2 id="modalReenviarTitle"><i class="fas fa-paper-plane"></i> Reenviar Token</h2>
            <p>Digite seu e-mail para receber um novo token</p>
        </div>

        <form id="formReenviarToken">
            <div class="form-group">
                <label for="emailReenviar"><i class="fas fa-envelope"></i> E-mail</label>
                <input type="email" id="emailReenviar" name="email" required />
            </div>

            <div class="token-actions">
                <button type="submit" class="btn-confirm">Enviar Token</button>
                <button type="button" class="btn-resend" id="cancelarReenviar">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById("googleLoginBtn").addEventListener("click", () => {
    window.location.href = "../codigos_php/google_auth.php";
});
</script>

<script defer src="assets/js/login.js"></script>

</body>
</html>
