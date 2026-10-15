<?php
session_start();

// Se o usuário já estiver logado, redireciona para o dashboard com URL amigável
if (isset($_SESSION['usuario_id'])) {
    header('Location: /dashboard');
    exit;
}

// Gera um token anti-CSRF / tempo de carregamento para a sessão se não existir
if (!isset($_SESSION['form_start_time'])) {
    $_SESSION['form_start_time'] = time();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Segurança & Forense - UNCISAL</title>
    <!-- Google Fonts & FontAwesome para ícones -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: radial-gradient(circle at 10% 20%, #090d16 0%, #0f172a 90%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e2e8f0;
            overflow: hidden;
        }

        .login-wrapper {
            width: 100%;
            max-width: 1100px;
            height: 640px;
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(51, 65, 85, 0.8);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            display: flex;
            overflow: hidden;
            margin: 20px;
        }

        /* Coluna da Esquerda (Painel Visual / Tema UNCISAL & Forense) */
        .login-banner {
            flex: 1.2;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.85), rgba(30, 41, 59, 0.95)), 
                        url('https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?auto=format&fit=crop&w=1200&q=80') center/cover no-repeat;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 50px;
            border-right: 1px solid rgba(51, 65, 85, 0.6);
            position: relative;
        }

        .login-banner::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 60%, rgba(15, 23, 42, 0.8));
            pointer-events: none;
        }

        .banner-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #38bdf8;
            letter-spacing: -0.5px;
            z-index: 1;
        }

        .banner-body {
            z-index: 1;
        }

        .banner-body h3 {
            font-size: 2.2rem;
            font-weight: 700;
            line-height: 1.25;
            margin-bottom: 15px;
            color: #f8fafc;
        }

        .banner-body p {
            font-size: 1rem;
            opacity: 0.85;
            line-height: 1.6;
            color: #94a3b8;
        }

        .tech-badge {
            display: inline-block;
            background: rgba(56, 189, 248, 0.12);
            color: #38bdf8;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-family: 'JetBrains Mono', monospace;
            margin-bottom: 18px;
            border: 1px solid rgba(56, 189, 248, 0.25);
        }

        .banner-footer {
            font-size: 0.85rem;
            opacity: 0.6;
            font-family: 'JetBrains Mono', monospace;
            z-index: 1;
        }

        /* Coluna da Direita (Formulário) */
        .login-form-container {
            flex: 1;
            padding: 50px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: rgba(30, 41, 59, 0.4);
        }

        .login-header {
            margin-bottom: 30px;
        }

        .login-header h2 {
            font-size: 1.85rem;
            color: #f8fafc;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .login-header p {
            color: #94a3b8;
            font-size: 0.92rem;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 1rem;
            transition: color 0.2s;
        }

        .input-with-icon input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 1px solid #475569;
            border-radius: 10px;
            font-size: 0.95rem;
            color: #f8fafc;
            transition: all 0.25s ease;
            outline: none;
            background-color: rgba(15, 23, 42, 0.6);
        }

        .input-with-icon input:focus {
            border-color: #38bdf8;
            background-color: rgba(15, 23, 42, 0.9);
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.15);
        }

        .input-with-icon input:focus + i,
        .input-with-icon input:not(:placeholder-shown) + i {
            color: #38bdf8;
        }

        /* CAMPO ARMADILHA (HONEYPOT) */
        .pot-field {
            display: none !important;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            border: none;
            border-radius: 10px;
            color: #ffffff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
            box-shadow: 0 6px 16px rgba(2, 132, 199, 0.4);
            transform: translateY(-1px);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .alert-msg {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.88rem;
            margin-bottom: 20px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.12);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .alert-warning {
            background-color: rgba(245, 158, 11, 0.12);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .card-footer {
            margin-top: 25px;
            text-align: center;
            font-size: 0.85rem;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        /* Responsividade Avançada */
        @media (max-width: 900px) {
            .login-wrapper {
                max-width: 480px;
                height: auto;
                margin: 15px;
            }
            .login-banner {
                display: none;
            }
            .login-form-container {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <!-- Lado Esquerdo: Banner Temático UNCISAL & Forense -->
        <div class="login-banner">
            <div class="banner-header">
                <h1><i class="fas fa-shield-halved"></i> UNCISAL | SegInfo</h1>
            </div>
            <div class="banner-body">
                <span class="tech-badge">Pós-Graduação • Análise Forense & SI</span>
                <h3>Análise e Resposta a Incidentes</h3>
                <p>Ambiente seguro operando sob os preceitos de <em>Secure by Design</em>, integrando criptografia de ponta, trilhas de auditoria e mitigação de vulnerabilidades OWASP Top 10.</p>
            </div>
            <div class="banner-footer">
                [SEC-OPS_CORE_ACTIVE // 2026]
            </div>
        </div>

        <!-- Lado Direito: Formulário de Acesso -->
        <div class="login-form-container">
            <div class="login-header">
                <h2>Acesso Restrito</h2>
                <p>Autenticação segura baseada em credenciais institucionais</p>
            </div>

            <?php
                if (isset($_GET['error'])) {
                    $errorMsg = "Credenciais inválidas ou acesso não autorizado!";
                    if ($_GET['error'] == 'blocked') {
                        $errorMsg = "Muitas tentativas falhas. IP sob quarentena temporária.";
                    } elseif ($_GET['error'] == 'bot') {
                        $errorMsg = "Acesso negado por suspeita de automação (Honeypot).";
                    }
                    echo '<div class="alert-msg alert-danger"><i class="fas fa-triangle-exclamation"></i> ' . $errorMsg . '</div>';
                }
                
                if (isset($_GET['timeout'])) {
                    echo '<div class="alert-msg alert-warning"><i class="fas fa-clock"></i> Sessão encerrada por inatividade. Reautentique-se.</div>';
                }
            ?>

            <form action="/auth" method="POST" autocomplete="off">
                
                <!-- CAMPO ARMADILHA (ANTI-BOT / HONEYPOT) -->
                <div class="pot-field">
                    <label for="website_url">Não preencha:</label>
                    <input type="text" id="website_url" name="website_url" value="" autocomplete="off">
                </div>

                <div class="input-group">
                    <label for="email">E-mail Institucional / Credencial</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user-shield"></i>
                        <input type="email" id="email" name="email" placeholder="usuario@uncisal.edu.br" required autocomplete="username">
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="senha">Chave de Acesso / Senha</label>
                    <div class="input-with-icon">
                        <i class="fas fa-key"></i>
                        <!-- Atributo placeholder removido para evitar exibição pré-preenchida -->
                        <input type="password" id="senha" name="senha" required autocomplete="current-password">
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-fingerprint"></i> Autenticar Sessão Segura
                </button>
            </form>
            
            <div class="card-footer">
                <i class="fas fa-lock"></i> Canal Criptografado TLS/PQC
            </div>
        </div>
    </div>

</body>
</html>