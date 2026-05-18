<?php
session_start();
require_once '../conexao.php';

// Se já estiver logado, redireciona para o painel
if (isset($_SESSION['admin_id'])) {
    header("Location: admin.php");
    exit;
}

// Verificar se a tabela tem administradores
try {
    $check_sql = "SELECT COUNT(*) as total FROM admin_usuarios";
    $check_stmt = $conn->query($check_sql);
    $total_admins = $check_stmt->fetchColumn();
    
    // Se não tem administradores, criar um automaticamente
    if ($total_admins == 0) {
        // Criar administrador padrão
        $usuario = "admin";
        $senha = "admin123";
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $ip_atual = $_SERVER['REMOTE_ADDR'];
        
        // Verificar e adicionar colunas se necessário
        try {
            $conn->exec("ALTER TABLE admin_usuarios ADD COLUMN IF NOT EXISTS nome_completo VARCHAR(100) DEFAULT 'Administrador'");
            $conn->exec("ALTER TABLE admin_usuarios ADD COLUMN IF NOT EXISTS ip_permitido VARCHAR(45)");
            $conn->exec("ALTER TABLE admin_usuarios ADD COLUMN IF NOT EXISTS ativo BOOLEAN DEFAULT true");
            $conn->exec("ALTER TABLE admin_usuarios ADD COLUMN IF NOT EXISTS bloqueado BOOLEAN DEFAULT false");
            $conn->exec("ALTER TABLE admin_usuarios ADD COLUMN IF NOT EXISTS nivel_acesso VARCHAR(20) DEFAULT 'super'");
            $conn->exec("ALTER TABLE admin_usuarios ADD COLUMN IF NOT EXISTS ultimo_login TIMESTAMP");
        } catch (Exception $e) {
            // Colunas já existem
        }
        
        $insert_sql = "INSERT INTO admin_usuarios 
                      (usuario, nome_completo, senha_hash, ip_permitido, nivel_acesso, criado_em) 
                      VALUES (:usuario, 'Administrador Principal', :senha_hash, :ip_permitido, 'super', NOW())";
        
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bindParam(':usuario', $usuario);
        $insert_stmt->bindParam(':senha_hash', $senha_hash);
        $insert_stmt->bindParam(':ip_permitido', $ip_atual);
        $insert_stmt->execute();
        
        $mensagem_info = "Usuário padrão criado: admin / admin123<br>Altere a senha após o primeiro login!";
    }
} catch (Exception $e) {
    $erro_sistema = "Erro no banco de dados: " . $e->getMessage();
}

// Processar login
$erro = '';
$ip_usuario = $_SERVER['REMOTE_ADDR'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if ($usuario && $senha) {
        try {
            // Buscar administrador
            $sql = "SELECT id, usuario, nome_completo, senha_hash, ip_permitido, ativo, bloqueado, nivel_acesso 
                    FROM admin_usuarios 
                    WHERE usuario = :usuario OR email = :usuario";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':usuario', $usuario);
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                // Verificar se está ativo
                if ($admin['ativo'] == 0) {
                    $erro = 'Esta conta está desativada.';
                }
                // Verificar se está bloqueado
                elseif ($admin['bloqueado'] == 1) {
                    $erro = 'Esta conta está bloqueada.';
                }
                // Verificar senha
                elseif (password_verify($senha, $admin['senha_hash'])) {
                    // VERIFICAÇÃO DE IP
                    $ip_permitido = trim($admin['ip_permitido']);
                    $ip_valido = true;
                    
                    if (!empty($ip_permitido)) {
                        if (strpos($ip_permitido, '*') !== false) {
                            // IP com wildcard (ex: 192.168.1.*)
                            $ip_base = substr($ip_permitido, 0, strpos($ip_permitido, '*'));
                            $ip_valido = (strpos($ip_usuario, $ip_base) === 0);
                        } else {
                            // IP específico
                            $ip_valido = ($ip_usuario === $ip_permitido);
                        }
                    }
                    
                    if (!$ip_valido) {
                        $erro = 'Acesso não permitido deste IP. Seu IP: ' . $ip_usuario;
                    } else {
                        // Login bem-sucedido
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_usuario'] = $admin['usuario'];
                        $_SESSION['admin_nome'] = $admin['nome_completo'];
                        $_SESSION['admin_nivel'] = $admin['nivel_acesso'];
                        
                        // Atualizar último login
                        $update_sql = "UPDATE admin_usuarios SET ultimo_login = NOW() WHERE id = :id";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bindParam(':id', $admin['id'], PDO::PARAM_INT);
                        $update_stmt->execute();
                        
                        // Redirecionar
                        header("Location: admin.php");
                        exit;
                    }
                } else {
                    $erro = 'Usuário ou senha incorretos.';
                }
            } else {
                $erro = 'Usuário ou senha incorretos.';
            }
        } catch (PDOException $e) {
            $erro = 'Erro no sistema. Tente novamente mais tarde.';
        }
    } else {
        $erro = 'Preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – Painel Administrativo</title>
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
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        color: #e6f0ff;
    }
    
    .login-container {
        width: 100%;
        max-width: 400px;
    }
    
    .login-box {
        background: #1a2029;
        border-radius: 15px;
        padding: 40px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        border: 1px solid #2a3a5a;
    }
    
    .titulo-admin {
        text-align: center;
        color: #e6f0ff;
        font-size: 2rem;
        margin-bottom: 10px;
        background: linear-gradient(135deg, #4da3ff 0%, #2d8cff 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .subtitulo-admin {
        text-align: center;
        color: #a0b3d6;
        margin-bottom: 30px;
        font-size: 1.1rem;
    }
    
    /* Campos do formulário */
    .campo {
        margin-bottom: 25px;
    }
    
    .campo label {
        display: block;
        color: #dceaff;
        margin-bottom: 8px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .campo input {
        width: 100%;
        padding: 14px;
        background: rgba(255, 255, 255, 0.05);
        border: 2px solid #24344d;
        border-radius: 8px;
        color: #e6f0ff;
        font-size: 1rem;
        transition: all 0.3s;
    }
    
    .campo input:focus {
        outline: none;
        border-color: #4da3ff;
        box-shadow: 0 0 0 3px rgba(77, 163, 255, 0.2);
    }
    
    .campo-senha {
        position: relative;
    }
    
    .icone-senha {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        width: 24px;
        height: 24px;
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23a0b3d6"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>');
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .icone-senha.mostrar {
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23a0b3d6"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>');
    }
    
    /* Botões */
    .btn-login {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #4da3ff 0%, #2d8cff 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        margin-top: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    
    .btn-login:hover {
        background: linear-gradient(135deg, #2d8cff 0%, #1a75ff 100%);
        transform: translateY(-2px);
    }
    
    .btn-login:active {
        transform: translateY(0);
    }
    
    /* Mensagens de erro/sucesso */
    .mensagem {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: 500;
    }
    
    .erro {
        background: linear-gradient(135deg, rgba(255, 77, 77, 0.1) 0%, rgba(204, 0, 0, 0.1) 100%);
        border: 1px solid #ff4d4d;
        color: #ff9999;
    }
    
    .info {
        background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 152, 0, 0.1) 100%);
        border: 1px solid #ffc107;
        color: #ffd966;
        font-size: 0.9rem;
    }
    
    .sucesso {
        background: linear-gradient(135deg, rgba(0, 204, 102, 0.1) 0%, rgba(0, 153, 77, 0.1) 100%);
        border: 1px solid #00cc66;
        color: #99ffcc;
    }
    
    /* Info IP */
    .ip-info {
        background: rgba(77, 163, 255, 0.1);
        border: 1px solid #4da3ff;
        border-radius: 8px;
        padding: 12px;
        margin: 20px 0;
        text-align: center;
        color: #a0d2ff;
        font-size: 0.9rem;
    }
    
    .ip-info strong {
        color: #4da3ff;
    }
    
    /* Footer */
    .login-footer {
        text-align: center;
        margin-top: 30px;
        color: #7a8ca5;
        font-size: 0.85rem;
        padding-top: 20px;
        border-top: 1px solid #2a3a5a;
    }
    
    .logo {
        font-size: 2.5rem;
        color: #4da3ff;
        margin-bottom: 15px;
        text-align: center;
    }
    
    .erro-sistema {
        background: linear-gradient(135deg, rgba(255, 77, 77, 0.2) 0%, rgba(204, 0, 0, 0.2) 100%);
        border: 2px solid #ff4d4d;
        color: #ff9999;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: bold;
    }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="logo">
                <i class="fas fa-user-shield"></i>
            </div>
            
            <h1 class="titulo-admin">Hackers Brasil</h1>
            <p class="subtitulo-admin">Acesso Administrativo</p>
            
            <?php if (isset($erro_sistema)): ?>
            <div class="erro-sistema">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($erro_sistema); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($mensagem_info)): ?>
            <div class="mensagem info">
                <i class="fas fa-info-circle"></i> <?php echo $mensagem_info; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($erro): ?>
            <div class="mensagem erro">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($erro); ?>
            </div>
            <?php endif; ?>
            
            <div class="ip-info">
                <i class="fas fa-network-wired"></i> Seu IP: <strong id="ipAtual"><?php echo htmlspecialchars($ip_usuario); ?></strong>
            </div>
            
            <form action="" method="POST">
                <div class="campo">
                    <label for="login_usuario"><i class="fas fa-user"></i> Usuário</label>
                    <input type="text" id="login_usuario" name="usuario" required 
                           placeholder="Digite seu usuário ou email"
                           value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>"
                           autofocus>
                </div>
                
                <div class="campo">
                    <label for="login_senha"><i class="fas fa-lock"></i> Senha</label>
                    <div class="campo-senha">
                        <input type="password" id="login_senha" name="senha" required 
                               placeholder="Digite sua senha">
                        <span id="toggleLoginSenha" class="icone-senha"></span>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Entrar no Painel
                </button>
            </form>
            
            <div class="login-footer">
                <p><i class="fas fa-shield-alt"></i> Sistema Protegido por Controle de IP</p>
                <p>Apenas IPs autorizados terão acesso</p>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
    // Toggle senha
    const loginSenha = document.getElementById("login_senha");
    const toggleLoginSenha = document.getElementById("toggleLoginSenha");
    
    if (loginSenha && toggleLoginSenha) {
        toggleLoginSenha.addEventListener("click", () => {
            if (loginSenha.type === "password") {
                loginSenha.type = "text";
                toggleLoginSenha.classList.add("mostrar");
            } else {
                loginSenha.type = "password";
                toggleLoginSenha.classList.remove("mostrar");
            }
        });
    }
    
    // Focar no campo de usuário
    document.getElementById('login_usuario').focus();
    
    // Verificar IP externo (apenas para informação)
    fetch('https://api.ipify.org?format=json')
    .then(response => response.json())
    .then(data => {
        const ipElement = document.getElementById('ipAtual');
        if (ipElement) {
            ipElement.textContent = data.ip;
        }
    })
    .catch(error => {
        console.log('Não foi possível obter IP externo');
    });
    </script>
</body>
</html>
