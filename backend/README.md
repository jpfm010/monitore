# Backend do Sentinela

API PHP 8.3 + MySQL/MariaDB para hospedagem própria/aaPanel.

## Instalação pela web (recomendado para hospedagem)
1. Envie o projeto completo para uma pasta isolada, por exemplo `/www/wwwroot/sentinela.seudominio.com/`.
2. No aaPanel, crie um banco MySQL vazio e um usuário exclusivo para esse banco. Não reutilize o banco de outro site.
3. Configure o domínio e o SSL/HTTPS no aaPanel.
4. Configure o Document Root do domínio para a pasta `backend/public` dentro do projeto. Assim `backend/config.php` e `backend/schema.sql` ficam fora da raiz pública.
5. Garanta que o PHP consiga gravar temporariamente na pasta `backend/` sem usar permissão 777. O instalador precisa criar `config.php` e `installed.lock`; depois, restrinja as permissões.
6. Abra `https://SEU-DOMINIO/install.php`, informe os dados do banco criado e defina a conta administrativa.
7. Quando aparecer a confirmação de sucesso, **remova `backend/public/install.php` do servidor**. O instalador também se bloqueia ao encontrar `config.php` ou `installed.lock`.
8. Abra `https://SEU-DOMINIO/login.html` e entre com a conta criada.

O instalador web testa a conexão, cria as tabelas do `schema.sql`, grava o arquivo privado de configuração e cria o primeiro administrador. Ele não cria o banco no servidor, não instala extensões do PHP, não configura DNS/SSL e não altera outros sites. Não use um banco com tabelas/contas de outros sistemas. Se ocorrer erro, consulte o log PHP do aaPanel; detalhes internos não são exibidos na página.

## Instalação manual alternativa
1. Crie um banco MySQL vazio e um usuário dedicado com permissões mínimas.
2. Importe `schema.sql`.
3. Configure `config.php` com as credenciais, fora do diretório público.
4. Mantenha HTTPS habilitado e `secure_cookies=true`.
5. Crie a primeira conta pelo terminal: `php backend/create-admin.php` (CLI; somente quando não houver usuários).

## Escopo desta etapa
- Sessão PHP com cookie HttpOnly e SameSite=Strict.
- Login com `password_verify`, regeneração do ID de sessão e consultas preparadas.
- Token CSRF para operações de alteração.
- Gestão de dispositivos cadastrados, declaração de autorização e revogação.
- Auditoria de login, logout e cadastro.
- Eventos retornados apenas quando marcados como demonstração.
- Nenhuma coleta de WhatsApp, notificações, GPS, chamadas ou mensagens reais.

## Rotas
- `GET api.php?action=health` — verificação simples da API.
- `GET api.php?action=csrf` — token CSRF da sessão.
- `POST api.php?action=login` — autenticação JSON: `email`, `password`.
- `POST api.php?action=logout` — encerra sessão; requer `X-CSRF-Token`.
- `GET api.php?action=me` — conta autenticada.
- `GET api.php?action=devices` — dispositivos cadastrados pelo usuário.
- `POST api.php?action=devices` — cadastra dispositivo com `label`, `platform`, `consent_confirmed: true` e `consent_scope`; registra autorização declarada, mas não conecta ao telefone.
- `POST api.php?action=devices-revoke` — revoga o consentimento cadastrado; JSON: `device_id`, com `X-CSRF-Token`.
- `GET api.php?action=events&device_id=ID` — retorna apenas eventos de demonstração.

## Antes de produção
Ainda faltam testes automatizados de API, limitação de tentativas de login, política de retenção/exclusão, integração completa das telas, revisão independente de segurança e testes em ambiente real.
