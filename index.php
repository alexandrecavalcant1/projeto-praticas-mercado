<?php
// Configurações de Cookies de Sessão Seguros (Secure by Design / OWASP A07)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Cabeçalhos HTTP de Segurança (Defesa em Profundidade)
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

// Se o usuário já estiver logado, redireciona para a área interna restrita
if (isset($_SESSION['logado']) && $_SESSION['logado'] === true && isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Geração de Token Anti-CSRF criptograficamente seguro
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Tratamento e mapeamento das mensagens de feedback
$alerta = null;
$tipoAlerta = 'danger';

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'blocked':
            $alerta = 'Acesso bloqueado temporariamente: Limite de 3 tentativas falhas atingido. Tente novamente em 5 minutos.';
            break;
        case 'bot':
            $alerta = 'Acesso negado: Atividade automatizada suspeita interceptada pelo Honeypot anti-bot.';
            break;
        case 'csrf':
            $alerta = 'Falha de segurança: Token CSRF inválido ou expirado. Recarregue e tente novamente.';
            break;
        case 'timeout':
            $alerta = 'Sessão encerrada por inatividade (15 minutos). Autentique-se novamente.';
            $tipoAlerta = 'warning';
            break;
        case 'db':
            $alerta = 'Serviço temporariamente indisponível. Erro ao conectar ao banco de dados.';
            break;
        case 'credenciais':
        default:
            $alerta = 'Credenciais inválidas ou conta não autorizada. Verifique seus dados.';
            break;
    }
} elseif (isset($_GET['logout'])) {
    $alerta = 'Sessão encerrada com sucesso. Os identificadores de sessão foram destruídos com segurança.';
    $tipoAlerta = 'success';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Segurança & Forense Digital - UNCISAL</title>
    <!-- Google Fonts & FontAwesome -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #0b1120;
            background-image: 
                radial-gradient(at 0% 0%, rgba(14, 165, 233, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(56, 189, 248, 0.08) 0px, transparent 50%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e2e8f0;
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 1060px;
            min-height: 620px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 18px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.05);
            display: flex;
            overflow: hidden;
        }

        /* Coluna da Esquerda (Painel Visual / Tema Forense & SegInfo) */
        .login-banner {
            flex: 1.15;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.92)), 
                        url('https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?auto=format&fit=crop&w=1200&q=80') center/cover no-repeat;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 45px;
            border-right: 1px solid #334155;
            position: relative;
        }

        .banner-header h1 {
            font-size: 1.45rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #38bdf8;
            letter-spacing: -0.5px;
        }

        .banner-header h1 i {
            color: #38bdf8;
            font-size: 1.6rem;
        }

        .tech-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(56, 189, 248, 0.12);
            color: #38bdf8;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-family: 'JetBrains Mono', monospace;
            margin-bottom: 20px;
            border: 1px solid rgba(56, 189, 248, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .banner-body h3 {
            font-size: 2.15rem;
            font-weight: 700;
            line-height: 1.25;
            margin-bottom: 16px;
            color: #f8fafc;
        }

        .banner-body p {
            font-size: 0.95rem;
            line-height: 1.65;
            color: #94a3b8;
            margin-bottom: 25px;
        }

        .security-features {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .security-features li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            color: #cbd5e1;
        }

        .security-features li i {
            color: #10b981;
            font-size: 0.9rem;
        }

        .banner-footer {
            font-size: 0.8rem;
            color: #64748b;
            font-family: 'JetBrains Mono', monospace;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
        }

        /* Coluna da Direita (Formulário Seguro) */
        .login-form-container {
            flex: 1;
            padding: 45px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #1e293b;
        }

        .login-header {
            margin-bottom: 24px;
        }

        .login-header h2 {
            font-size: 1.75rem;
            color: #f8fafc;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .login-header p {
            color: #94a3b8;
            font-size: 0.88rem;
        }

        .alert-box {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            line-height: 1.4;
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.4);
        }

        .alert-warning {
            background-color: rgba(245, 158, 11, 0.15);
            color: #fcd34d;
            border: 1px solid rgba(245, 158, 11, 0.4);
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.15);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.4);
        }

        .input-group {
            margin-bottom: 18px;
        }

        .input-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 6px;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 0.95rem;
        }

        .input-with-icon input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #475569;
            border-radius: 8px;
            font-size: 0.92rem;
            color: #f8fafc;
            transition: all 0.2s ease;
            outline: none;
            background-color: #0f172a;
        }

        .input-with-icon input:focus {
            border-color: #38bdf8;
            background-color: #111d33;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }

        /* CAMPO ARMADILHA ANTI-BOT (HONEYPOT) - Invisível a humanos e acessibilidade, mas preenchido por bots */
        .hp-trap {
            position: absolute !important;
            left: -9999px !important;
            top: -9999px !important;
            opacity: 0 !important;
            height: 0 !important;
            width: 0 !important;
            pointer-events: none !important;
            z-index: -100 !important;
            overflow: hidden !important;
        }

        .btn-submit {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #0284c7, #0369a1);
            border: none;
            border-radius: 8px;
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #0369a1, #075985);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(2, 132, 199, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .credentials-hint {
            margin-top: 22px;
            background: rgba(15, 23, 42, 0.8);
            border: 1px dashed #334155;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 0.8rem;
            color: #94a3b8;
        }

        .credentials-hint strong {
            color: #38bdf8;
            display: block;
            margin-bottom: 4px;
            font-family: 'JetBrains Mono', monospace;
        }

        .credentials-hint code {
            color: #f1f5f9;
            background: #0f172a;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
        }

        .card-footer {
            margin-top: 22px;
            text-align: center;
            font-size: 0.8rem;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        /* Responsividade Mobile */
        @media (max-width: 860px) {
            .login-banner {
                display: none;
            }
            .login-wrapper {
                max-width: 480px;
                min-height: auto;
            }
            .login-form-container {
                padding: 35px 25px;
            }
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <!-- Lado Esquerdo: Banner Temático UNCISAL & Forense Digital -->
        <div class="login-banner">
            <div class="banner-header">
                <h1><i class="fas fa-shield-halved"></i> UNCISAL | SegInfo</h1>
            </div>
            
            <div class="banner-body">
                <span class="tech-pill"><i class="fas fa-microchip"></i> Pós-Graduação • Forense & SI</span>
                <h3>Análise e Resposta a Incidentes</h3>
                <p>Ambiente seguro desenvolvido sob rigorosas diretrizes de <strong>Secure by Design</strong>, mitigando proativamente as principais vulnerabilidades listadas no OWASP Top 10.</p>
                
                <ul class="security-features">
                    <li><i class="fas fa-shield-check"></i> Prepared Statements contra SQL Injection (OWASP A03)</li>
                    <li><i class="fas fa-shield-check"></i> Proteção contra Session Fixation & Hijacking (OWASP A07)</li>
                    <li><i class="fas fa-shield-check"></i> Defesa ativa contra Força Bruta (Bloqueio após 3 falhas)</li>
                    <li><i class="fas fa-shield-check"></i> Armadilha Honeypot anti-bot e validação de tokens CSRF</li>
                </ul>
            </div>
            
            <div class="banner-footer">
                <span>[SEC-OPS_NODE_ACTIVE]</span>
                <span>PADRÃO: SECURE BY DESIGN</span>
            </div>
        </div>

        <!-- Lado Direito: Formulário Seguro -->
        <div class="login-form-container">
            <div class="login-header">
                <h2>Autenticação Segura</h2>
                <p>Credenciais criptografadas e monitoradas por telemetria SOC</p>
            </div>

            <?php if ($alerta): ?>
                <div class="alert-box alert-<?= $tipoAlerta ?>">
                    <i class="fas <?= $tipoAlerta === 'success' ? 'fa-circle-check' : ($tipoAlerta === 'warning' ? 'fa-clock' : 'fa-triangle-exclamation') ?>"></i>
                    <div><?= htmlspecialchars($alerta, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <form action="auth.php" method="POST" autocomplete="on">
                
                <!-- TOKEN ANTI-CSRF (OWASP A01 / Broken Access Control & Cross-Site Forgery) -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <!-- ARMADILHA HONEYPOT ANTI-BOT (Campos invisíveis para usuários reais) -->
                <div class="hp-trap" aria-hidden="true">
                    <label for="contact_fax_hp">Não preencher:</label>
                    <input type="text" id="contact_fax_hp" name="contact_fax_hp" value="" tabindex="-1" autocomplete="off">
                    <input type="text" id="website_url" name="website_url" value="" tabindex="-1" autocomplete="off">
                </div>

                <div class="input-group">
                    <label for="email">E-mail Institucional</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user-shield"></i>
                        <input type="email" id="email" name="email" placeholder="admin@uncisal.edu.br" required autocomplete="email" autofocus>
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="senha">Chave de Acesso / Senha</label>
                    <div class="input-with-icon">
                        <i class="fas fa-key"></i>
                        <input type="password" id="senha" name="senha" placeholder="••••••••••••" required autocomplete="current-password">
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-fingerprint"></i> Autenticar Sessão
                </button>
            </form>

            <div class="credentials-hint">
                <strong><i class="fas fa-terminal"></i> Credenciais de Avaliação (Pós):</strong>
                E-mail: <code>admin@uncisal.edu.br</code><br>
                Senha: <code>Admin@123456</code>
            </div>
            
            <div class="card-footer">
                <i class="fas fa-lock"></i> Canal Criptografado TLS / Proteção OWASP Top 10
            </div>
        </div>
    </div>

</body>
</html>