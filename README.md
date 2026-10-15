# Sentinela — Painel familiar

Protótipo original de painel familiar transparente, com interface responsiva e backend inicial PHP 8.3 + MySQL/MariaDB.

## O que existe
- Interface demonstrativa responsiva (HTML/CSS/JavaScript).
- Login por sessão, hash de senha, consultas preparadas e token CSRF para operações de alteração.
- Cadastro administrativo de dispositivos com declaração de autorização e revogação.
- Registro de auditoria e API para eventos demonstrativos.
- Instalador web PHP em `backend/public/install.php` para configuração inicial em domínio próprio.

## Instalação no domínio / aaPanel
Leia [backend/README.md](backend/README.md). Resumo: crie um banco MySQL vazio e usuário dedicado no aaPanel, configure SSL/HTTPS, defina o Document Root para `backend/public`, acesse `https://SEU-DOMINIO/install.php`, preencha o formulário e depois **apague `backend/public/install.php` do servidor**. O instalador não cria banco no aaPanel nem configura DNS ou SSL.

## Limitações
Este repositório ainda está em desenvolvimento, não é um produto pronto para produção. Não existe conexão real com WhatsApp, captura de mensagens, GPS, chamadas ou monitoramento oculto. Os dados de atividade são fictícios. Antes de produção faltam testes de integração, limitação de tentativas de login, política de retenção e exclusão, revisão de segurança e validação em ambiente de homologação.

Qualquer integração futura deverá utilizar fontes oficiais/autorizadas, consentimento válido e transparência para as pessoas envolvidas.
