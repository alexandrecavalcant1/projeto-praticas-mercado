# Projeto Aplicado: Práticas de Mercado - Secure by Design

Este repositório contém o protótipo de software desenvolvido para a avaliação final da disciplina, implementando uma esteira completa de DevSecOps, infraestrutura segura em nuvem (Oracle Cloud Free Tier) e automação via GitHub Actions.

## 🚀 Eixo 1: Infraestrutura e Acesso
- **IP Público:** `163.176.146.51`
- **Endereço SSL/TLS:** `https://163.176.146.51.nip.io`
- **Servidor Web:** Nginx (com redirecionamento automático de HTTP para HTTPS e suporte a TLS/PQC)
- **Segurança de Acesso:** Chaves SSH obrigatórias e Fail2Ban configurado na porta 22.

## 📦 Eixo 2: Versionamento e Segurança
- **Repositório:** Público no GitHub (`projeto-praticas-mercado`)
- **Boas Práticas:** Uso rigoroso de `.gitignore` para exclusão de arquivos de ambiente (`.env`), chaves privadas e credenciais.

## 💻 Eixo 3: Mitigações de Segurança (OWASP Top 10)
Este projeto mitiga ativamente três vulnerabilidades do OWASP Top 10:

1. **Broken Access Control (A01:2021):** Mitigada no arquivo `dashboard.php` através de uma validação rigorosa da variável `$_SESSION['logado']` no topo do código. Qualquer acesso direto à URL sem autenticação prévia é bloqueado e o usuário é redirecionado.
2. **Injection / Cross-Site Scripting (A03:2021):** Mitigada no arquivo `login.php`. Todas as mensagens de erro retornadas ao usuário são filtradas pela função `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`, garantindo a sanitização da saída de dados e impedindo a injeção e execução de scripts maliciosos na interface.
3. **Identification and Authentication Failures (A07:2021):** Mitigada no arquivo `login.php`. Após a validação bem-sucedida das credenciais, o sistema utiliza a função `session_regenerate_id(true)`. Isso previne ataques de Fixação de Sessão (*Session Fixation*), renovando o identificador do usuário antes de conceder o acesso à área restrita.

---
*Desenvolvido com práticas de Secure by Design e automação CI/CD via GitHub Actions.*