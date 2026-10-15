# Projeto Aplicado: Práticas de Mercado - Secure by Design

Este repositório contém o protótipo de software desenvolvido para a avaliação final da pós-graduação em **Segurança da Informação & Análise Forense Digital (UNCISAL)**, implementando princípios rigorosos de **Secure by Design**, esteira DevSecOps, infraestrutura segura em nuvem (Oracle Cloud Free Tier) e automação via GitHub Actions.

---

## 🚀 Eixo 1: Infraestrutura e Acesso
- **IP Público:** `163.176.146.51`
- **Domínio com SSL/TLS:** `https://projetoforense.freedynamicdns.net`
- **Servidor Web:** Nginx (redirecionamento obrigatório HTTP -> HTTPS, suporte a TLS moderno)
- **Segurança de Host:** Autenticação SSH restrita por chave criptográfica e Fail2Ban configurado.

---

## 📦 Eixo 2: Arquitetura dos Três Arquivos Principais

O sistema foi estruturado em três arquivos centrais em PHP que atuam de forma coordenada e resiliente:

### 1. `index.php` (Tela de Login & Defesa Perimetral)
- **Interface Estilizada:** Design moderno com estética Dark Cyber / SOC Forense (UNCISAL SegInfo).
- **Mecanismo Anti-Bot Honeypot:** Campos armadilha invisíveis no DOM (`contact_fax_hp` e `website_url`) que interceptam e bloqueiam crawlers e robôs antes de processar qualquer requisição.
- **Proteção Anti-CSRF:** Geração e validação de tokens pseudoaleatórios criptográficos (`random_bytes`).
- **Headers HTTP de Segurança:** `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection`, `Referrer-Policy` e `Permissions-Policy`.
- **Mensagens Contextuais:** Tratamento seguro de feedbacks de erro (credenciais inválidas, bloqueio temporário, suspeita de bot, expiração de sessão).

### 2. `auth.php` (Backend de Autenticação Segura)
- **Mitigação de SQL Injection (OWASP A03):** Conexão via PDO com consultas parametrizadas obrigatórias (*Prepared Statements*) e `ATTR_EMULATE_PREPARES => false`.
- **Prevenção de Fixação de Sessão (OWASP A07):** Invocação compulsória de `session_regenerate_id(true)` no momento da validação das credenciais.
- **Rate Limiting & Bloqueio por Força Bruta:** Monitoramento do campo `tentativas_falhas`. Após **3 erros consecutivos**, a credencial é posta em quarentena com bloqueio temporário (`bloqueado_ate`) de 5 minutos.
- **Hashing Criptográfico de Senhas:** Validação via `password_verify()` e geração com algoritmo padrão da indústria (`BCRYPT`).
- **Mitigação de Timing Attacks:** Execução de hash simulado com tempo uniforme em casos de usuário inexistente para impedir enumeração de contas (*Account Enumeration*).
- **Alta Resiliência de Conexão:** Tenta MySQL primariamente e possui fallback automático para SQLite em ambientes locais de avaliação.

### 3. `dashboard.php` (Área Restrita & Telemetria)
- **Controle de Acesso Quebrado (OWASP A01):** Validação estrita server-side da sessão (`$_SESSION['logado']` e `$_SESSION['usuario_id']`). Acessos diretos sem autenticação são imediatamente interceptados.
- **Anti-Session Hijacking:** Validação da assinatura do `User-Agent` da requisição comparada com o momento da autenticação.
- **Timeout por Inatividade:** Destruição automática da sessão após 15 minutos (900 segundos) sem atividade do usuário.
- **Mitigação de XSS (OWASP A03):** Escapamento rigoroso de variáveis de saída com `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- **Botão de Logout Seguro:** Destruição total das variáveis de sessão, invalidação do cookie no navegador e redirecionamento seguro para `index.php?logout=1`.

---

## 🔑 Credenciais Padrão para Avaliação

| Papel | E-mail Institucional | Senha |
|---|---|---|
| Administrador SOC | `admin@uncisal.edu.br` | `Admin@123456` |

---

## 🗄️ Estrutura do Banco de Dados

Caso queira importar manualmente no MySQL, utilize o arquivo `schema.sql`:

```sql
CREATE DATABASE IF NOT EXISTS `sistema_seguranca` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sistema_seguranca`;

CREATE TABLE `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `senha` VARCHAR(255) NOT NULL,
    `tentativas_falhas` INT DEFAULT 0,
    `bloqueado_ate` DATETIME NULL,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🛡️ Matriz de Mitigações OWASP Top 10 & Práticas de Mercado

| Vulnerabilidade OWASP | Risco Mitigado | Arquivo / Mecanismo de Defesa |
|---|---|---|
| **A01: Broken Access Control** | Acesso indevido a páginas internas | `dashboard.php`: checagem de sessão no topo e amarração ao User-Agent |
| **A02: Cryptographic Failures** | Exposição e roubo de credenciais | Criptografia forte via `password_hash()` BCRYPT e tráfego HTTPS/TLS |
| **A03: Injection** | SQL Injection e Cross-Site Scripting (XSS) | `auth.php` (PDO Prepared Statements) e `dashboard.php` (`htmlspecialchars`) |
| **A07: Identification & Auth Failures** | Força Bruta e Fixação de Sessão | `auth.php` (`session_regenerate_id(true)` e bloqueio após 3 tentativas) |
| **Anti-Automation Defense** | Scanners maliciosos e spiders | `index.php` e `auth.php` (Defesa Ativa com Honeypot invisível) |

---
*Desenvolvido em conformidade com os preceitos de Secure by Design para a Pós-Graduação em Segurança da Informação & Análise Forense Digital.*