<?php
session_start();

$erro = '';

// Processamento do formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';

    // Autenticação estática para o protótipo (Simulando uma base de dados)
    if ($usuario === 'admin' && $senha === 'admin123') {
        
        // OWASP Mitigação 3: Falhas de Autenticação (Prevenção contra Session Fixation)
        // Regenera o ID da sessão após o login bem-sucedido
        session_regenerate_id(true); 
        
        $_SESSION['logado'] = true;
        $_SESSION['usuario_nome'] = 'Administrador';
        
        header('Location: dashboard.php');
        exit;
    } else {
        $erro = 'Usuário ou senha incorretos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Login | Alexandre Soluções digitais</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page" style="background-color: #121212;">
<div class="login-box">
    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <a href="#" class="h1 text-dark"><b>Sistema</b> Seguro</a>
        </div>
        <div class="card-body">
            <p class="login-box-msg">Faça login para iniciar sua sessão</p>

            <?php if ($erro): ?>
                <div class="alert alert-danger text-center">
                    <!-- OWASP Mitigação 2: Injection (Prevenção contra XSS) -->
                    <!-- Sanitização da saída de dados para evitar execução de scripts -->
                    <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="post">
                <div class="input-group mb-3">
                    <input type="text" name="usuario" class="form-control" placeholder="Usuário (admin)" required>
                    <div class="input-group-append">
                        <div class="input-group-text"><span class="fas fa-user"></span></div>
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="senha" class="form-control" placeholder="Senha (admin123)" required>
                    <div class="input-group-append">
                        <div class="input-group-text"><span class="fas fa-lock"></span></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-block font-weight-bold">Entrar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>