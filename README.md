# Projeto Aplicado: Práticas de Mercado - Secure by Design

Este repositório contém o protótipo de software desenvolvido para a avaliação final da disciplina, implementando uma esteira completa de DevSecOps.

## 🛡️ Eixo 3: Mitigações de Segurança (OWASP Top 10)
*(A ser preenchido durante o desenvolvimento)*
1. **Vulnerabilidade X:** Mitigada no arquivo `Y` através da técnica `Z`.
2. **Vulnerabilidade A:** Mitigada no arquivo `B` através da técnica `C`.
## 🛡️ Eixo 3: Mitigações de Segurança (OWASP Top 10)
Este projeto mitiga ativamente três vulnerabilidades do OWASP Top 10:

1. **Broken Access Control (A01:2021):** Mitigada no arquivo `dashboard.php` através de uma validação rigorosa da variável `$_SESSION['logado']` no topo do código. Qualquer acesso direto à URL sem autenticação prévia é bloqueado e o usuário é redirecionado.
2. **Injection / Cross-Site Scripting (A03:2021):** Mitigada no arquivo `login.php`. Todas as mensagens de erro retornadas ao usuário são filtradas pela função `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`, garantindo a sanitização da saída de dados e impedindo a injeção e execução de scripts maliciosos na interface.
3. **Identification and Authentication Failures (A07:2021):** Mitigada no arquivo `login.php`. Após a validação bem-sucedida das credenciais, o sistema utiliza a função `session_regenerate_id(true)`. Isso previne ataques de Fixação de Sessão (*Session Fixation*), renovando o identificador do usuário antes de conceder o acesso à área restrita.

## 🚀 Eixo 1: Infraestrutura e Acesso
- **IP Público:** `163.176.146.51`
- **Domínio SSL:** `https://163.176.146.51.nip.io`
- **Servidor Web:** Nginx