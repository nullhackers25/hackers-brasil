<?php
session_start();
require_once '../conexao.php';

// Impedir acesso sem login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Buscar APENAS usuários bloqueados
$sql = "SELECT u.id, u.nome_completo, u.email, u.usuario, u.ip_cadastro, 
               u.navegador, u.sistema_operacional, u.criado_em, u.provider, 
               u.google_id, u.bloqueado_ate,
               b.motivo, b.criado_em as bloqueado_em
        FROM usuarios u
        LEFT JOIN bloqueios_usuarios b ON u.id = b.usuario_id 
            AND b.tipo = 'manual'
            AND b.id = (
                SELECT MAX(id) 
                FROM bloqueios_usuarios 
                WHERE usuario_id = u.id 
                AND tipo = 'manual'
            )
        WHERE u.bloqueado_ate IS NOT NULL 
        AND u.bloqueado_ate > NOW()
        ORDER BY u.bloqueado_ate ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$usuarios_bloqueados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar total de bloqueados
$sql_count = "SELECT COUNT(*) as total FROM usuarios 
              WHERE bloqueado_ate IS NOT NULL 
              AND bloqueado_ate > NOW()";
$count_stmt = $conn->query($sql_count);
$total_bloqueados = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários Bloqueados</title>
    <link rel="stylesheet" href="usuarios_bank.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos específicos para esta página */
        .header-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 15px;
            background: linear-gradient(135deg, #1b263b 0%, #24344d 100%);
            border-radius: 10px;
            border: 1px solid #2a3a5a;
        }
        
        .total-badge {
            background: linear-gradient(135deg, #ff3333 0%, #cc0000 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tempo-restante {
            font-size: 0.85rem;
            color: #ff9999;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .coluna-motivo {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .coluna-motivo:hover {
            white-space: normal;
            overflow: visible;
            position: relative;
            z-index: 10;
            background: #0d1117;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
        }
        
        .vazio-bloqueados {
            text-align: center;
            padding: 80px 20px;
            color: #7a8ca5;
        }
        
        .vazio-bloqueados i {
            font-size: 5rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .vazio-bloqueados h3 {
            color: #4da3ff;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1><i class="fas fa-lock"></i> Usuários Bloqueados</h1>

    <div class="header-info">
        <div>
            <h2 style="color: #4da3ff; margin: 0; font-size: 1.3rem;">
                <i class="fas fa-users-slash"></i> Visualização de Bloqueios
            </h2>
            <p style="color: #dceaff; margin: 5px 0 0 0; font-size: 0.9rem;">
                Apenas visualização. Para gerenciar bloqueios, use a página principal.
            </p>
        </div>
        
        <div class="total-badge">
            <i class="fas fa-user-lock"></i>
            <?= $total_bloqueados ?> usuário(s) bloqueado(s)
        </div>
    </div>

    <div class="painel-acoes">
        <a href="usuarios.php" class="btn voltar">
            <i class="fas fa-arrow-left"></i> Voltar para Usuários
        </a>
        <a href="usuarios_bank.php" class="btn editar-tabela">
            <i class="fas fa-edit"></i> Gerenciar Bloqueios
        </a>
        <a href="dashboard.php" class="btn logout">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
    </div>

    <div class="table-container">
        <table class="tabela">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome Completo</th>
                    <th>Email</th>
                    <th>Usuário</th>
                    <th>IP Cadastro</th>
                    <th>Bloqueado Até</th>
                    <th>Tempo Restante</th>
                    <th>Motivo</th>
                    <th>Bloqueado Em</th>
                    <th>Provider</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($usuarios_bloqueados as $usuario): 
                    $timestampBloqueio = strtotime($usuario['bloqueado_ate']);
                    $timestampAtual = time();
                    $diferenca = $timestampBloqueio - $timestampAtual;
                    
                    // Calcular tempo restante formatado
                    $tempoRestante = '';
                    if ($diferenca > 0) {
                        if ($diferenca < 3600) {
                            // Menos de 1 hora
                            $minutos = ceil($diferenca / 60);
                            $tempoRestante = "$minutos minuto(s)";
                        } elseif ($diferenca < 86400) {
                            // Menos de 1 dia
                            $horas = floor($diferenca / 3600);
                            $tempoRestante = "$horas hora(s)";
                        } elseif ($diferenca < 2592000) {
                            // Menos de 30 dias
                            $dias = floor($diferenca / 86400);
                            $tempoRestante = "$dias dia(s)";
                        } else {
                            // Mais de 30 dias
                            $meses = floor($diferenca / 2592000);
                            $tempoRestante = "$meses mês(es)";
                        }
                    }
                ?>
                <tr>
                    <td><?= htmlspecialchars($usuario['id']) ?></td>
                    <td><?= htmlspecialchars($usuario['nome_completo']) ?></td>
                    <td><?= htmlspecialchars($usuario['email']) ?></td>
                    <td><?= htmlspecialchars($usuario['usuario']) ?></td>
                    <td><?= htmlspecialchars($usuario['ip_cadastro']) ?></td>
                    <td>
                        <span class="status-bloqueado">
                            <i class="fas fa-lock"></i> 
                            <?= date('d/m/Y H:i', $timestampBloqueio) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($diferenca > 0): ?>
                            <div class="tempo-restante">
                                <i class="fas fa-hourglass-half"></i>
                                <?= $tempoRestante ?>
                            </div>
                        <?php else: ?>
                            <span style="color: #00cc66;">
                                <i class="fas fa-check"></i> Expirou
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="coluna-motivo" title="<?= htmlspecialchars($usuario['motivo'] ?? 'Sem motivo informado') ?>">
                        <?= htmlspecialchars($usuario['motivo'] ?? 'Sem motivo') ?>
                    </td>
                    <td>
                        <?= $usuario['bloqueado_em'] ? date('d/m/Y H:i', strtotime($usuario['bloqueado_em'])) : 'N/A' ?>
                    </td>
                    <td>
                        <span class="provider-badge provider-<?= htmlspecialchars($usuario['provider'] ?? 'local') ?>">
                            <?= htmlspecialchars($usuario['provider'] ?? 'local') ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (empty($usuarios_bloqueados)): ?>
    <div class="vazio-bloqueados">
        <i class="fas fa-user-check"></i>
        <h3>Nenhum usuário bloqueado</h3>
        <p>Todos os usuários estão ativos no momento.</p>
        <a href="usuarios.php" class="btn editar-tabela" style="margin-top: 20px;">
            <i class="fas fa-users"></i> Ver todos os usuários
        </a>
    </div>
    <?php endif; ?>
</div>

<script>
// Adicionar estilos inline para badges
document.head.insertAdjacentHTML('beforeend', `
<style>
.status-bloqueado {
    background: linear-gradient(135deg, #ff3333 0%, #cc0000 100%);
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.provider-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.provider-google {
    background: linear-gradient(135deg, #DB4437 0%, #C53929 100%);
    color: white;
}

.provider-local {
    background: linear-gradient(135deg, #4285F4 0%, #357AE8 100%);
    color: white;
}

/* Estilos responsivos */
@media (max-width: 768px) {
    .header-info {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .coluna-motivo {
        max-width: 150px;
    }
}

@media (max-width: 576px) {
    .tempo-restante {
        font-size: 0.75rem;
    }
    
    .coluna-motivo {
        max-width: 120px;
    }
}
</style>
`);

// Atualizar tempo restante em tempo real
function atualizarTempos() {
    document.querySelectorAll('.tempo-restante').forEach(element => {
        const texto = element.textContent.trim();
        if (texto.includes('minuto')) {
            // Atualizar minutos
            setTimeout(atualizarTempos, 60000); // Atualizar a cada minuto
        } else if (texto.includes('hora')) {
            setTimeout(atualizarTempos, 600000); // Atualizar a cada 10 minutos
        }
    });
}

// Iniciar atualização se houver usuários bloqueados
<?php if (!empty($usuarios_bloqueados)): ?>
document.addEventListener('DOMContentLoaded', atualizarTempos);
<?php endif; ?>
</script>

</body>
</html>
