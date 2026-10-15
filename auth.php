<?php
session_start();

// Configurações do Banco de Dados MySQL (Sem senha, conforme ajustado)
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
    // Tratamento seguro de erro para não expor detalhes técnicos do banco ao usuário
    error_log("Erro de conexão DB: " . $e->getMessage());
    header('Location: /?error=db');
    exit;
}

// Verifica estritamente se a requisição foi enviada via método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

// 1. Defesa Anti-Bot (Honeypot): Se o campo armadilha foi preenchido, trata como automação maliciosa
if (!empty($_POST['website_url'])) {
    header('Location: /?error=bot');
    exit;
}

// 2. Sanitização e filtragem do e-mail e senha recebidos do formulário
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$senha = $_POST['senha'] ?? '';

if (empty($email) || empty($senha)) {
    header('Location: /?error=credenciais');
    exit;
}

// 3. Consulta segura ao banco utilizando Prepared Statements (Mitigação contra SQL Injection - OWASP A03)
$stmt = $pdo->prepare("SELECT id, nome, email, senha, tentativas_falhas, bloqueado_ate FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$usuario = $stmt->fetch();

$agora = time();

if ($usuario) {
    // 4. Verifica se o usuário está sob quarentena/bloqueio temporário por excesso de falhas
    if (!empty($usuario['bloqueado_ate']) && strtotime($usuario['bloqueado_ate']) > $agora) {
        header('Location: /?error=blocked');
        exit;
    }

    // 5. Validação criptográfica da senha (Password Hashing)
    if (password_verify($senha, $usuario['senha'])) {
        // Sucesso: Reseta o contador de falhas e limpa a data de bloqueio
        $stmtReset = $pdo->prepare("UPDATE usuarios SET tentativas_falhas = 0, bloqueado_ate = NULL WHERE id = ?");
        $stmtReset->execute([$usuario['id']]);

        // Prevenção crítica contra roubo de sessão - Session Fixation (OWASP A07)
        session_regenerate_id(true);

        // Inicialização limpa da sessão autenticada
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['logado'] = true;

        // Redirecionamento amigável para o painel restrito
        header('Location: /dashboard');
        exit;
    } else {
        // Senha Incorreta: Incrementa o contador de tentativas falhas
        $tentativas = $usuario['tentativas_falhas'] + 1;
        
        if ($tentativas >= 3) {
            // Regra de Negócio: Bloqueia a conta por 5 minutos (300 segundos) após 3 erros
            $bloqueioAte = date('Y-m-d H:i:s', $agora + 300);
            $stmtLock = $pdo->prepare("UPDATE usuarios SET tentativas_falhas = ?, bloqueado_ate = ? WHERE id = ?");
            $stmtLock->execute([$tentativas, $bloqueioAte, $usuario['id']]);
            
            header('Location: /?error=blocked');
            exit;
        } else {
            // Atualiza apenas o número de falhas acumuladas
            $stmtFail = $pdo->prepare("UPDATE usuarios SET tentativas_falhas = ? WHERE id = ?");
            $stmtFail->execute([$tentativas, $usuario['id']]);
        }
    }
}

// Resposta genérica proposital para evitar enumeração de contas (Account Enumeration Prevention)
header('Location: /?error=credenciais');
exit;