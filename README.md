# Sentinela — Painel familiar

Protótipo original de dashboard de controle parental transparente, com interface inspirada em padrões comuns de painéis de atividade. Não copia a marca ou o código proprietário do mSpy.

## O que existe no repositório
- Interface demonstrativa responsiva (HTML/CSS/JavaScript).
- Backend inicial PHP 8.3 + MySQL/MariaDB em `backend/`.
- Login por sessão, senha com hash, consultas preparadas e token CSRF para operações de alteração.
- Cadastro administrativo de dispositivos com declaração de autorização e revogação do consentimento.
- Registro de auditoria e API de eventos demonstrativos.
- Página de login e dashboard servidos sob `backend/public`.

## Instalação local / aaPanel
Leia primeiro [backend/README.md](backend/README.md). O Document Root recomendado é `backend/public`, com `backend/config.php` mantido fora da pasta pública. Importe `backend/schema.sql`, configure o arquivo privado de banco e crie o primeiro administrador pelo terminal com `php backend/create-admin.php`.

## Limitações atuais
Este repositório ainda é uma base em desenvolvimento, não um produto pronto para produção. Não existe conexão real com WhatsApp, captura de mensagens, GPS, chamadas ou monitoramento oculto. Os dados de atividade apresentados são fictícios. Ainda faltam testes automatizados, hardening, limitação de tentativas de login, política de retenção, integração completa das telas de dispositivos com a API e revisão de segurança.

Qualquer futura integração deverá utilizar fontes oficiais/autorizadas, consentimento válido e transparência para as pessoas envolvidas.
