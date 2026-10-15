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
    <title>Login - ACTI Soluções Digitais</title>
    <!-- Google Fonts & FontAwesome para ícones -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #f4f6f9;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-wrapper {
            width: 100%;
            max-width: 1000px;
            height: 600px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            display: flex;
            overflow: hidden;
            margin: 20px;
        }

        /* Coluna da Esquerda (Painel Visual / Imagem) */
        .login-banner {
            flex: 1;
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.85), rgba(15, 23, 42, 0.9)), 
                        url('https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=1000&q=80') center/cover no-repeat;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 40px;
        }

        .banner-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .banner-body h3 {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 15px;
        }

        .banner-body p {
            font-size: 0.95rem;
            opacity: 0.85;
            line-height: 1.5;
        }

        .banner-footer {
            font-size: 0.85rem;
            opacity: 0.7;
        }

        /* Coluna da Direita (Formulário) */
        .login-form-container {
            flex: 1;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header {
            margin-bottom: 25px;
        }

        .login-header h2 {
            font-size: 1.75rem;
            color: #1e293b;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .login-header p {
            color: #64748b;
            font-size: 0.9rem;
        }

        .input-group {
            margin-bottom: 18px;
        }

        .input-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
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
            color: #94a3b8;
        }

        .input-with-icon input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #1e293b;
            transition: all 0.2s ease;
            outline: none;
            background-color: #f8fafc;
        }

        .input-with-icon input:focus {
            border-color: #0d6efd;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
        }

        /* CAMPO ARMADILHA (HONEYPOT) - Invisível para humanos, visível apenas para bots */
        .pot-field {
            display: none !important;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: #0d6efd;
            border: none;
            border-radius: 8px;
            color: #ffffff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-top: 5px;
        }

        .btn-submit:hover {
            background-color: #0b5ed7;
        }

        .alert-msg {
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 15px;
            text-align: center;
        }

        .alert-danger {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .alert-warning {
            background-color: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }

        .card-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 0.85rem;
        }

        /* Responsividade para telas menores */
        @media (max-width: 768px) {
            .login-banner {
                display: none;
            }
            .login-wrapper {
                max-width: 450px;
                height: auto;
            }
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <!-- Lado Esquerdo: Banner Ilustrativo -->
        <div class="login-banner">
            <div class="banner-header">
                <h1><i class="fas fa-layer-group"></i> ACTI Soluções</h1>
            </div>
            <div class="banner-body">
                <h3>Gestão Inteligente & Segurança</h3>
                <p>Ambiente seguro desenvolvido sob os princípios de Secure by Design e conformidade com as exigências de mercado.</p>
            </div>
            <div class="banner-footer">
                &copy; 2026 Alexandre Soluções Digitais. Todos os direitos reservados.
            </div>
        </div>

        <!-- Lado Direito: Formulário de Acesso -->
        <div class="login-form-container">
            <div class="login-header">
                <h2>Bem-vindo de volta!</h2>
                <p>Insira suas credenciais para acessar o painel</p>
            </div>

            <?php
                if (isset($_GET['error'])) {
                    $errorMsg = "E-mail ou senha inválidos!";
                    if ($_GET['error'] == 'blocked') {
                        $errorMsg = "Muitas tentativas falhas. Aguarde alguns minutos.";
                    } elseif ($_GET['error'] == 'bot') {
                        $errorMsg = "Acesso negado por suspeita de automação.";
                    }
                    echo '<div class="alert-msg alert-danger"><i class="fas fa-exclamation-circle"></i> ' . $errorMsg . '</div>';
                }
                
                if (isset($_GET['timeout'])) {
                    echo '<div class="alert-msg alert-warning"><i class="fas fa-clock"></i> Sua sessão expirou por inatividade. Faça login novamente.</div>';
                }
            ?>

            <form action="/auth" method="POST">
                
                <!-- CAMPO ARMADILHA (ANTI-BOT / HONEYPOT) -->
                <div class="pot-field">
                    <label for="website_url">Não preencha este campo se for humano:</label>
                    <input type="text" id="website_url" name="website_url" value="" autocomplete="off">
                </div>

                <div class="input-group">
                    <label for="email">E-mail</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="seuemail@exemplo.com" required autocomplete="email">
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="senha">Senha</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="senha" name="senha" placeholder="Sua senha de acesso" required>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-sign-in-alt"></i> Entrar no Sistema
                </button>
            </form>
            
            <div class="card-footer"></div>
        </div>
    </div>

</body>
</html>