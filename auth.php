<?php
session_start();

// Exibir erros temporariamente para depuração (remova em produção se necessário)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurações do Banco de Dados MySQL (Sem senha)
$host = 'localhost';
$dbname = 'sistema_seguranca';
$username = 'root';
$password = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erro crítico de conexão com o banco de dados: " . $e->getMessage());
}

// Verifica estritamente se a requisição foi enviada via método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

// 1. Defesa Anti-Bot (Honeypot)
if (!empty($_POST['website_url'])) {
    header('Location: /?error=bot');
    exit;
}

// 2. Sanitização do e-mail e captura da senha
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$senha = $_POST['senha'] ?? '';

if (empty($email) || empty($senha)) {
    header('Location: /?error=credenciais');
    exit;
}

// 3. Consulta segura ao banco utilizando Prepared Statements
$stmt = $pdo->prepare("SELECT id, nome, email, senha, tentativas_falhas, bloqueado_ate FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$usuario = $stmt->fetch();

$agora = time();

if ($usuario) {
    // 4. Verifica se o usuário está bloqueado por excesso de tentativas
    if (!empty($usuario['bloqueado_ate']) && strtotime($usuario['bloqueado_ate']) > $agora) {
        header('Location: /?error=blocked');
        exit;
    }

    // 5. Validação da Senha
    if (password_verify($senha, $usuario['senha'])) {
        // Sucesso: Reseta tentativas e bloqueio
        $stmtReset = $pdo->prepare("UPDATE usuarios SET tentativas_falhas = 0, bloqueado_ate = NULL WHERE id = ?");
        $stmtReset->execute([$usuario['id']]);

        // Prevenção contra Session Fixation (OWASP A07)
        session_regenerate_id(true);

        // Inicializa sessão
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['logado'] = true;

        header('Location: /dashboard');
        exit;
    } else {
        // Senha incorreta: Incrementa tentativas
        $tentativas = $usuario['tentativas_falhas'] + 1;
        
        if ($tentativas >= 3) {
            $bloqueioAte = date('Y-m-d H:i:s', $agora + 300); // 5 minutos de bloqueio
            $stmtLock = $pdo->prepare("UPDATE usuarios SET tentativas_falhas = ?, bloqueado_ate = ? WHERE id = ?");
            $stmtLock->execute([$tentativas, $bloqueioAte, $usuario['id']]);
            
            header('Location: /?error=blocked');
            exit;
        } else {
            $stmtFail = $pdo->prepare("UPDATE usuarios SET tentativas_falhas = ? WHERE id = ?");
            $stmtFail->execute([$tentativas, $usuario['id']]);
        }
    }
}

// Erro padrão de credenciais inválidas
header('Location: /?error=credenciais');
exit;