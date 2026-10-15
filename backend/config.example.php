<?php
declare(strict_types=1);

/*
 * Copie para backend/config.php e preencha localmente.
 * Nunca publique credenciais reais no Git.
 */
return [
    'db' => [
        'host' => getenv('SENTINELA_DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('SENTINELA_DB_PORT') ?: 3306),
        'name' => getenv('SENTINELA_DB_NAME') ?: 'sentinela',
        'user' => getenv('SENTINELA_DB_USER') ?: 'sentinela_app',
        'pass' => getenv('SENTINELA_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'session_name' => 'sentinela_session',
    'secure_cookies' => true,
    'allowed_origins' => [], // Same-origin por padrão; informe origens exatas se necessário.
];
