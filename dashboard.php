<?php
session_start();

// OWASP Mitigação 1: Broken Access Control (Controle de Acesso Quebrado)
// Verifica se a sessão existe; se não, bloqueia o acesso e redireciona
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Dashboard | Alexandre Soluções digitais</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
        <div class="container">
            <a href="#" class="navbar-brand">
                <i class="fas fa-shield-alt text-primary mr-2"></i>
                <span class="brand-text font-weight-light"><b>Projeto</b> Aplicado</span>
            </a>
            <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto">
                <li class="nav-item">
                    <a href="logout.php" class="btn btn-outline-danger btn-sm mt-1">
                        <i class="fas fa-sign-out-alt"></i> Sair
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="content-wrapper mt-5">
        <div class="content">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card card-success card-outline shadow">
                            <div class="card-header">
                                <h5 class="card-title m-0">Bem-vindo, <?= htmlspecialchars($_SESSION['usuario_nome'], ENT_QUOTES, 'UTF-8') ?>!</h5>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title mb-3 text-success"><i class="fas fa-check-circle"></i> Acesso Restrito Autorizado</h6>
                                <p class="card-text">
                                    Esta é a área segura do sistema. Você só está vendo esta página porque passou pela autenticação e seu controle de sessão (Broken Access Control) está ativo e validado.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="main-footer text-center">
        <strong>&copy; <?= date('Y') ?> Alexandre Soluções digitais.</strong> Todos os direitos reservados.
    </footer>
</div>
</body>
</html>