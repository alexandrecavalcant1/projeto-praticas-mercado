<?php
// Configurações de Sessão e Cookies Seguros (Secure by Design / OWASP A07)
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

// Cabeçalhos HTTP de Segurança
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// -------------------------------------------------------------
// 1. MITIGAÇÃO: BROKEN ACCESS CONTROL (OWASP A01:2021)
// -------------------------------------------------------------
// Validação obrigatória da sessão ativa antes de renderizar qualquer conteúdo
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

// -------------------------------------------------------------
// 2. MITIGAÇÃO: SESSION HIJACKING (AMARRAÇÃO AO USER-AGENT)
// -------------------------------------------------------------
$currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $currentUserAgent) {
    // Violação de integridade detectada: destrói a sessão imediatamente
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header('Location: index.php?error=credenciais');
    exit;
}

// -------------------------------------------------------------
// 3. TIMEOUT DE SESSÃO POR INATIVIDADE (15 MINUTOS)
// -------------------------------------------------------------
$limiteInatividade = 900; // 15 minutos em segundos
if (isset($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso'] > $limiteInatividade)) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header('Location: index.php?error=timeout');
    exit;
}

// Renova o timestamp de última atividade da sessão
$_SESSION['ultimo_acesso'] = time();

// Recupera dados da sessão sanitizados
$nomeUsuario = htmlspecialchars($_SESSION['usuario_nome'] ?? 'Operador', ENT_QUOTES, 'UTF-8');
$emailUsuario = htmlspecialchars($_SESSION['usuario_email'] ?? 'usuario@uncisal.edu.br', ENT_QUOTES, 'UTF-8');
$sessionIdMascarado = htmlspecialchars(substr(session_id(), 0, 8) . '****************' . substr(session_id(), -4), ENT_QUOTES, 'UTF-8');
$ipUsuario = htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Controle Restrito | UNCISAL SegInfo</title>
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
                radial-gradient(at 0% 0%, rgba(14, 165, 233, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(56, 189, 248, 0.05) 0px, transparent 50%);
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Barra de Navegação Superior */
        .top-navbar {
            background-color: #1e293b;
            border-bottom: 1px solid #334155;
            padding: 15px 35px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .brand-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
            color: #38bdf8;
            font-size: 1.6rem;
        }

        .brand-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #f8fafc;
            letter-spacing: -0.3px;
        }

        .brand-title span {
            color: #38bdf8;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .soc-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(16, 185, 129, 0.12);
            color: #10b981;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-family: 'JetBrains Mono', monospace;
            border: 1px solid rgba(16, 185, 129, 0.3);
            font-weight: 600;
        }

        .soc-status-badge .dot {
            width: 8px;
            height: 8px;
            background-color: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 8px #10b981;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.4; }
            100% { opacity: 1; }
        }

        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            background: #ef4444;
            color: #ffffff;
            border-color: #ef4444;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        /* Conteúdo Principal */
        .main-container {
            flex: 1;
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 35px 25px;
        }

        /* Banner de Boas-Vindas */
        .welcome-card {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .welcome-info h2 {
            font-size: 1.7rem;
            color: #f8fafc;
            margin-bottom: 6px;
            font-weight: 700;
        }

        .welcome-info p {
            color: #94a3b8;
            font-size: 0.95rem;
        }

        .user-tag {
            display: inline-block;
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
        }

        .badge-verified {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Grid de Proteções Secure by Design */
        .section-header {
            margin-bottom: 20px;
        }

        .section-header h3 {
            font-size: 1.25rem;
            color: #f8fafc;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-header h3 i {
            color: #38bdf8;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .feature-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 22px;
            transition: all 0.2s ease;
        }

        .feature-card:hover {
            border-color: #475569;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.25);
        }

        .feature-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            background: rgba(56, 189, 248, 0.12);
            color: #38bdf8;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .feature-card.success .feature-icon {
            background: rgba(16, 185, 129, 0.12);
            color: #10b981;
        }

        .feature-card.warning .feature-icon {
            background: rgba(245, 158, 11, 0.12);
            color: #f59e0b;
        }

        .feature-title {
            font-size: 1rem;
            font-weight: 600;
            color: #f1f5f9;
        }

        .feature-owasp {
            font-size: 0.75rem;
            font-family: 'JetBrains Mono', monospace;
            color: #94a3b8;
        }

        .feature-desc {
            font-size: 0.85rem;
            color: #94a3b8;
            line-height: 1.6;
        }

        /* Painel de Telemetria e Auditoria */
        .telemetry-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 25px;
        }

        .telemetry-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .telemetry-item {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 15px;
        }

        .telemetry-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 6px;
            font-family: 'JetBrains Mono', monospace;
        }

        .telemetry-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #38bdf8;
            font-family: 'JetBrains Mono', monospace;
            word-break: break-all;
        }

        /* Rodapé */
        .main-footer {
            background: #0f172a;
            border-top: 1px solid #1e293b;
            padding: 20px 30px;
            text-align: center;
            font-size: 0.8rem;
            color: #64748b;
        }

        .main-footer strong {
            color: #94a3b8;
        }

        @media (max-width: 768px) {
            .top-navbar {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            .nav-actions {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>

    <!-- Barra Superior -->
    <header class="top-navbar">
        <div class="brand-container">
            <i class="fas fa-shield-halved brand-logo"></i>
            <div class="brand-title">UNCISAL <span>| SegInfo</span></div>
        </div>

        <div class="nav-actions">
            <div class="soc-status-badge">
                <span class="dot"></span>
                <span>SEC-OPS ATIVO</span>
            </div>

            <a href="logout.php" class="btn-logout">
                <i class="fas fa-power-off"></i> Sair do Sistema
            </a>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="main-container">
        
        <!-- Cartão de Boas-Vindas -->
        <section class="welcome-card">
            <div class="welcome-info">
                <h2>Bem-vindo, <?= $nomeUsuario ?></h2>
                <p>Credencial Autenticada: <span class="user-tag"><?= $emailUsuario ?></span></p>
            </div>

            <div class="badge-verified">
                <i class="fas fa-user-check"></i> Sessão Autorizada & Validada
            </div>
        </section>

        <!-- Mitigações Secure by Design -->
        <div class="section-header">
            <h3><i class="fas fa-lock"></i> Controles de Segurança Implementados (Secure by Design)</h3>
        </div>

        <section class="features-grid">
            
            <div class="feature-card success">
                <div class="feature-header">
                    <div class="feature-icon"><i class="fas fa-key"></i></div>
                    <div>
                        <div class="feature-title">Broken Access Control</div>
                        <div class="feature-owasp">OWASP A01:2021</div>
                    </div>
                </div>
                <p class="feature-desc">Validação estrita no topo de cada rota restrita via <code>$_SESSION['logado']</code>, mitigando acessos não autorizados diretos pela URL e sequestro por User-Agent.</p>
            </div>

            <div class="feature-card success">
                <div class="feature-header">
                    <div class="feature-icon"><i class="fas fa-database"></i></div>
                    <div>
                        <div class="feature-title">Defesa contra SQL Injection</div>
                        <div class="feature-owasp">OWASP A03:2021</div>
                    </div>
                </div>
                <p class="feature-desc">Backend orientado a PDO com Prepared Statements e emulação desabilitada (<code>ATTR_EMULATE_PREPARES => false</code>), neutralizando qualquer injeção maliciosa de SQL.</p>
            </div>

            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon"><i class="fas fa-rotate"></i></div>
                    <div>
                        <div class="feature-title">Prevenção de Session Fixation</div>
                        <div class="feature-owasp">OWASP A07:2021</div>
                    </div>
                </div>
                <p class="feature-desc">Chamada atômica de <code>session_regenerate_id(true)</code> pós-login, garantindo que o token da sessão anterior seja revogado e descartado imediatamente.</p>
            </div>

            <div class="feature-card warning">
                <div class="feature-header">
                    <div class="feature-icon"><i class="fas fa-user-lock"></i></div>
                    <div>
                        <div class="feature-title">Rate Limiting & Anti-Brute Force</div>
                        <div class="feature-owasp">Defesa em Camadas</div>
                    </div>
                </div>
                <p class="feature-desc">Controle de tentativas falhas com bloqueio automático temporário (quarentena de 5 minutos) caso o limite de 3 tentativas incorretas consecutivas seja atingido.</p>
            </div>

            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon"><i class="fas fa-bug-slash"></i></div>
                    <div>
                        <div class="feature-title">Defesa Ativa Honeypot Anti-Bot</div>
                        <div class="feature-owasp">Anti-Automation Defense</div>
                    </div>
                </div>
                <p class="feature-desc">Campos armadilha imperceptíveis para usuários legítimos, mas preenchidos por robôs e scanners web, bloqueando requisições automatizadas antes do backend.</p>
            </div>

            <div class="feature-card success">
                <div class="feature-header">
                    <div class="feature-icon"><i class="fas fa-code"></i></div>
                    <div>
                        <div class="feature-title">Sanitização contra XSS</div>
                        <div class="feature-owasp">OWASP A03:2021</div>
                    </div>
                </div>
                <p class="feature-desc">Todas as variáveis renderizadas no DOM passam por <code>htmlspecialchars(..., ENT_QUOTES, 'UTF-8')</code>, impedindo a injeção de scripts maliciosos refletidos ou gravados.</p>
            </div>

        </section>

        <!-- Telemetria da Sessão em Tempo Real -->
        <section class="telemetry-card">
            <div class="section-header">
                <h3><i class="fas fa-fingerprint"></i> Telemetria da Sessão & Auditoria de Acesso</h3>
            </div>
            
            <div class="telemetry-grid">
                <div class="telemetry-item">
                    <div class="telemetry-label">IP do Cliente</div>
                    <div class="telemetry-value"><?= $ipUsuario ?></div>
                </div>
                <div class="telemetry-item">
                    <div class="telemetry-label">Identificador de Sessão</div>
                    <div class="telemetry-value"><?= $sessionIdMascarado ?></div>
                </div>
                <div class="telemetry-item">
                    <div class="telemetry-label">Criptografia de Senha</div>
                    <div class="telemetry-value">BCRYPT (Algoritmo Forte)</div>
                </div>
                <div class="telemetry-item">
                    <div class="telemetry-label">Timeout por Inatividade</div>
                    <div class="telemetry-value">900s (15 min)</div>
                </div>
            </div>
        </section>

    </main>

    <!-- Rodapé -->
    <footer class="main-footer">
        <p><strong>Pós-Graduação em Segurança da Informação & Análise Forense Digital</strong> — UNCISAL</p>
        <p>&copy; <?= date('Y') ?> Projeto Aplicado | Arquitetura Secure by Design e Práticas de Mercado.</p>
    </footer>

</body>
</html>