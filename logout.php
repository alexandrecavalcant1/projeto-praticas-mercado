<?php
// Configurações de Sessão e Cookies Seguros
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Limpa todas as variáveis de sessão da memória do servidor
$_SESSION = [];

// 2. Destrói o cookie de sessão no navegador do cliente (limpeza forçada)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Destrói a sessão persistida no servidor
session_destroy();

// 4. Redireciona para a tela inicial de autenticação informando logout bem-sucedido
header("Location: index.php?logout=1");
exit;