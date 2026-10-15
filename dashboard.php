<?php
session_start();

// Validação estrita de acesso: Mitigação OWASP A01 (Broken Access Control)
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['usuario_id'])) {
    session_unset();
    session_destroy();
    header('Location: /?error=acesso_negado');
    exit;
}

// Timeout de inatividade (15 minutos)
$inatividadeMax = 900; 
if (isset($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso'] > $inatividadeMax)) {
    session_unset();
    session_destroy();
    header('Location: /?timeout=1');
    exit;
}
$_SESSION['ultimo_acesso'] = time();

// Conexão com o Banco de Dados para carregar os logs e estatísticas
$host = 'localhost';
$dbname = 'sistema_seguranca';
$username = 'root';
$password = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Busca os últimos 25 logs de acesso
    $logs = $pdo->query("SELECT * FROM logs_acesso ORDER BY data_hora DESC LIMIT 25")->fetchAll();

    // Conta total de acessos com sucesso e falhas para os indicadores
    $totalSucessos = $pdo->query("SELECT COUNT(*) FROM logs_acesso WHERE status = 'SUCESSO'")->fetchColumn();
    $totalFalhas = $pdo->query("SELECT COUNT(*) FROM logs_acesso WHERE status IN ('FALHA', 'BLOQUEADO', 'QUARENTENA')")->fetchColumn();
} catch (PDOException $e) {
    $logs = [];
    $totalSucessos = 0;
    $totalFalhas = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Controle - Segurança Forense & SOC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: #0f172a; color: #e2e8f0; min-height: 100vh; display: flex; flex-direction: column; }
        
        header { background: #1e293b; border-bottom: 1px solid #334155; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        header h1 { font-size: 1.25rem; color: #38bdf8; display: flex; align-items: center; gap: 10px; font-family: 'JetBrains Mono', monospace; }
        
        .user-info { display: flex; align-items: center; gap: 20px; font-size: 0.9rem; color: #94a3b8; }
        .user-info span { color: #f8fafc; font-weight: 600; }

        .btn-logout { background: #ef4444; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: background 0.2s; }
        .btn-logout:hover { background: #dc2626; }

        .container { max-width: 1200px; margin: 35px auto; padding: 0 20px; width: 100%; }
        
        /* Grid de Indicadores e Status */
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .metric-card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 22px; display: flex; align-items: center; gap: 18px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
        .metric-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
        .metric-icon.blue { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
        .metric-icon.green { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .metric-icon.red { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        
        .metric-details h3 { font-size: 1.6rem; font-weight: 700; color: #f8fafc; font-family: 'JetBrains Mono', monospace; }
        .metric-details p { color: #94a3b8; font-size: 0.82rem; margin-top: 3px; }

        /* Cards Gerais */
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 30px; margin-bottom: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
        .card h2 { color: #f8fafc; margin-bottom: 15px; font-size: 1.35rem; display: flex; align-items: center; gap: 10px; }
        .card p { color: #94a3b8; line-height: 1.6; margin-bottom: 12px; font-size: 0.95rem; }

        .badge-secure { display: inline-flex; align-items: center; gap: 6px; background: rgba(16, 185, 129, 0.1); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.2); padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; font-family: 'JetBrains Mono', monospace; margin-top: 10px; }

        /* Tabela de Logs */
        .table-responsive { width: 100%; overflow-x: auto; margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; }
        th, td { padding: 12px 15px; border-bottom: 1px solid #334155; white-space: nowrap; }
        th { background: #0f172a; color: #94a3b8; font-family: 'JetBrains Mono', monospace; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; }
        tr:hover { background: rgba(51, 65, 85, 0.2); }

        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; font-family: 'JetBrains Mono', monospace; display: inline-block; }
        .badge-sucesso { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .badge-falha { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        .badge-bloqueado { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        
        code { font-family: 'JetBrains Mono', monospace; background: #0f172a; padding: 3px 6px; border-radius: 4px; color: #38bdf8; font-size: 0.85rem; }
    </style>
</head>
<body>

    <header>
        <h1><i class="fas fa-terminal"></i> UNCISAL // SEC-OPS Dashboard</h1>
        <div class="user-info">
            <div>Operador: <span><?php echo htmlspecialchars($_SESSION['usuario_nome'], ENT_QUOTES, 'UTF-8'); ?></span></div>
            <a href="/logout" class="btn-logout"><i class="fas fa-power-off"></i> Encerrar Sessão</a>
        </div>
    </header>

    <div class="container">
        
        <!-- Cartões de Boas-Vindas e Métricas -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="metric-details">
                    <h3><?php echo $totalSucessos; ?></h3>
                    <p>Acessos Registrados</p>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon red"><i class="fas fa-ban"></i></div>
                <div class="metric-details">
                    <h3><?php echo $totalFalhas; ?></h3>
                    <p>Tentativas Bloqueadas / Falhas</p>
                </div>
            </div>
        </div>

        <!-- Seção de Boas-Vindas e Sessão Protegida -->
        <div class="card">
            <h2>Bem-vindo, <?php echo htmlspecialchars($_SESSION['usuario_nome'], ENT_QUOTES, 'UTF-8'); ?>!</h2>
            <p>Você acessou com sucesso a área restrita do sistema. Esta sessão encontra-se isolada e protegida por mecanismos avançados de controle de acesso.</p>
            <p><strong>E-mail Vinculado:</strong> <?php echo htmlspecialchars($_SESSION['usuario_email'], ENT_QUOTES, 'UTF-8'); ?></p>
            
            <div class="badge-secure">
                <i class="fas fa-shield-check"></i> Sessão Autenticada • ID Protegido contra Session Fixation
            </div>
        </div>

        <!-- Seção de Conformidade OWASP -->
        <div class="card">
            <h2><i class="fas fa-shield-halved"></i> Status de Conformidade da Aplicação</h2>
            <p>O protótipo atende rigorosamente aos critérios de <em>Secure by Design</em> propostos no projeto aplicado:</p>
            <p><strong>Mitigações OWASP Top 10 Ativas:</strong></p>
            <ul style="color: #94a3b8; margin-left: 20px; line-height: 1.6; font-size: 0.92rem;">
                <li><strong>A01: Broken Access Control:</strong> Validação estrita de sessão ativa em todas as rotas restritas.</li>
                <li><strong>A03: Injection:</strong> Uso exclusivo de Prepared Statements (PDO) para interações seguras com o MySQL.</li>
                <li><strong>A07: Identification and Authentication Failures:</strong> Proteção contra força bruta (bloqueio após 3 falhas) e regeneração segura de ID de sessão.</li>
            </ul>
        </div>

        <!-- Tabela de Auditoria e Logs de Acesso em Tempo Real -->
        <div class="card">
            <h2><i class="fas fa-history"></i> Trilha de Auditoria & Forense (Logs de Acesso)</h2>
            <p>Registro imutável das tentativas de autenticação capturadas no ambiente de produção:</p>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data / Hora</th>
                            <th>E-mail Informado</th>
                            <th>Endereço IP</th>
                            <th>Status</th>
                            <th>Detalhes do Evento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #64748b; padding: 25px;">Nenhum log registrado até o momento.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>#<?php echo $log['id']; ?></td>
                                <td style="font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: #94a3b8;"><?php echo $log['data_hora']; ?></td>
                                <td><strong><?php echo htmlspecialchars($log['email_informado']); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($log['ip_origem']); ?></code></td>
                                <td>
                                    <?php 
                                        $status = $log['status'];
                                        $classeBadge = 'badge-falha';
                                        if ($status === 'SUCESSO') $classeBadge = 'badge-sucesso';
                                        elseif ($status === 'BLOQUEADO' || $status === 'QUARENTENA' || $status === 'BOT_BLOQUEADO') $classeBadge = 'badge-bloqueado';
                                    ?>
                                    <span class="badge <?php echo $classeBadge; ?>"><?php echo $status; ?></span>
                                </td>
                                <td style="color: #94a3b8; font-size: 0.85rem;"><?php echo htmlspecialchars($log['detalhes']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>