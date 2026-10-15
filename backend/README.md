# Backend inicial do Sentinela

API PHP 8.3 + MySQL/MariaDB, preparada para implantação em hospedagem própria/aaPanel.

## Escopo desta etapa
- Sessão PHP com cookie HttpOnly e SameSite=Strict.
- Login com `password_verify`, regeneração do ID de sessão e consultas preparadas.
- Token CSRF para operações de alteração.
- Gestão inicial de dispositivos cadastrados e registro explícito de autorização.
- Auditoria de login, logout e cadastro.
- Eventos retornados apenas quando marcados como demonstração.
- Nenhuma coleta de WhatsApp, notificações, GPS, chamadas ou mensagens reais.

## Preparação
1. Crie um banco MySQL vazio e um usuário dedicado com permissões mínimas.
2. Importe `schema.sql`.
3. Copie `config.example.php` para `config.php), fora do diretório público, e preencha as credenciais usando variáveis de ambiente ou arquivo protegido.
4. Configure o Document Root para `backend/public` para a API ou integre as rotas ao front-end por proxy/mesma origem.
5. Habilite HTTPS. Em produção, mantenha `secure_cookies=true`.
6. Crie a primeira conta administrativa por um instalador CLI seguro ou script temporário que seja removido após o uso. Ainda não existe criação automática de administrador nesta etapa.

## Rotas
- `GET api.php?action=health` — verificação simples da API.
- `GET api.php?action=csrf` — token CSRF da sessão.
- `POST api.php?action=login` — autenticação JSON: `email`, `password`.
- `POST api.php?action=logout` — encerra sessão; requer `X-CSRF-Token`.
- `GET api.php?action=me` — conta autenticada.
- `GET api.php?action=devices` — dispositivos cadastrados pelo usuário.
- `POST api.php?action=devices` — cadastra dispositivo com `label`, `platform`, `consent_confirmed: true` e `consent_scope`; registra autorização declarada, mas não conecta ao telefone.
- `GET api.php?action=events&device_id=ID` — retorna apenas eventos de demonstração.

## Antes de produção
Faltam testes automatizados, limitação de tentativas no servidor, política de retenção/exclusão, fluxo formal de revogação de consentimento, instalador administrativo seguro, integração com o front-end, revisão independente de segurança e testes em ambiente real.
