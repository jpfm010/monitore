<?php
declare(strict_types=1);

/**
 * Instalador web do Sentinela.
 * Cria tabelas e conta inicial; não executa comandos de sistema nem altera outros sites.
 * Remova este arquivo após a instalação.
 */
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
if (!$isHttps && !$isLocal) {
    http_response_code(400);
    exit('Por segurança, abra o instalador usando HTTPS. Configure o certificado SSL do domínio no painel de hospedagem.');
}

session_name('sentinela_installer');
session_set_cookie_params(['httponly' => true, 'secure' => $isHttps, 'samesite' => 'Strict', 'path' => '/']);
session_start();
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');

$backendDir = dirname(__DIR__);
$configPath = $backendDir . '/config.php';
$lockPath = $backendDir . '/installed.lock';
$schemaPath = $backendDir . '/schema.sql';
$installed = is_file($lockPath) || is_file($configPath);

function esc(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function page(string $title, string $message, bool $success = false): never {
    $color = $success ? '#166534' : '#991b1b';
    echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'
        . esc($title) . ' — Sentinela</title><style>body{font:16px system-ui;background:#f3f5f9;color:#172033;margin:0;padding:24px}.card{max-width:720px;margin:5vh auto;background:#fff;padding:28px;border-radius:16px;box-shadow:0 8px 32px #10204012}h1{margin-top:0}p{line-height:1.6}.notice{padding:14px;border-radius:8px;background:#f8fafc;border-left:4px solid ' . $color . '}a{color:#1d4ed8}</style><main class="card"><h1>'
        . esc($title) . '</h1><p class="notice">' . esc($message) . '</p><p>Sentinela · instalador web</p></main></html>';
    exit;
}

if ($installed) {
    page('Instalação já iniciada', 'O instalador foi bloqueado porque já existe configuração ou um marcador de instalação. Não apague esses arquivos para tentar reinstalar. Para reinstalar, faça backup e siga um procedimento manual seguro.');
}
if (!is_file($schemaPath) || !is_readable($schemaPath)) {
    page('Arquivos incompletos', 'Não encontrei backend/schema.sql. Envie o projeto completo ao servidor antes de executar a instalação.');
}
if (!is_writable($backendDir)) {
    page('Permissão necessária', 'O PHP precisa poder criar backend/config.php e backend/installed.lock. Ajuste temporariamente a permissão da pasta backend no painel de hospedagem, sem dar permissão 777, e tente novamente.');
}
if (empty($_SESSION['installer_csrf'])) $_SESSION['installer_csrf'] = bin2hex(random_bytes(32));
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = (string)($_POST['csrf'] ?? '');
    if (!hash_equals($_SESSION['installer_csrf'], $csrf)) {
        $error = 'Sessão de instalação inválida. Atualize a página e tente novamente.';
    } else {
        $host = trim((string)($_POST['db_host'] ?? '127.0.0.1'));
        $port = filter_var($_POST['db_port'] ?? '3306', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
        $dbName = trim((string)($_POST['db_name'] ?? ''));
        $dbUser = trim((string)($_POST['db_user'] ?? ''));
        $dbPass = (string)($_POST['db_pass'] ?? '');
        $adminName = trim((string)($_POST['admin_name'] ?? ''));
        $adminEmail = filter_var(trim((string)($_POST['admin_email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $adminPassword = (string)($_POST['admin_password'] ?? '');

        if (!preg_match('/^[a-zA-Z0-9.-]{1,190}$/', $host)
            || !$port
            || !preg_match('/^[a-zA-Z0-9_]{1,64}$/', $dbName)
            || $dbUser === '' || strlen($dbUser) > 190
            || mb_strlen($adminName) < 2 || mb_strlen($adminName) > 120
            || !$adminEmail || strlen($adminPassword) < 12 || strlen($adminPassword) > 1024) {
            $error = 'Revise os campos. A senha administrativa precisa ter no mínimo 12 caracteres.';
        } else {
            $config = [
                'db' => ['host' => $host, 'port' => (int)$port, 'name' => $dbName, 'user' => $dbUser, 'pass' => $dbPass, 'charset' => 'utf8mb4'],
                'session_name' => 'sentinela_session',
                'secure_cookies' => true,
                'allowed_origins' => [],
            ];
            try {
                $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, (int)$port, $dbName);
                $pdo = new PDO($dsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                $sql = file_get_contents($schemaPath);
                if ($sql === false) throw new RuntimeException('Não foi possível ler o esquema SQL.');
                $statements = preg_split('/;\s*(?:\r?\n|$)/', trim($sql));
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if ($statement !== '') $pdo->exec($statement);
                }

                $check = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
                if ((int)$check > 0) throw new RuntimeException('A tabela users já contém contas. Use um banco vazio para uma instalação nova.');
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, active) VALUES (?, ?, ?, ?, 1)');
                $stmt->execute([$adminName, $adminEmail, password_hash($adminPassword, PASSWORD_DEFAULT), 'admin']);

                $configText = "<?php\ndeclare(strict_types=1);\n\nreturn " . var_export($config, true) . ";\n";
                $tmpPath = $configPath . '.tmp';
                if (file_put_contents($tmpPath, $configText, LOCK_EX) === false) throw new RuntimeException('Não foi possível gravar a configuração.');
                @chmod($tmpPath, 0640);
                if (!rename($tmpPath, $configPath)) {
                    @unlink($tmpPath);
                    throw new RuntimeException('Não foi possível finalizar a configuração.');
                }
                @chmod($configPath, 0640);
                $lockContents = 'Instalado em UTC: ' . gmdate('c') . PHP_EOL;
                if (file_put_contents($lockPath, $lockContents, LOCK_EX) === false) {
                    @unlink($configPath);
                    throw new RuntimeException('Não foi possível gravar o marcador de instalação.');
                }
                @chmod($lockPath, 0640);
                unset($dbPass, $adminPassword);
                page('Instalação concluída', 'Banco conectado, tabelas criadas e conta administrativa inicial criada. Por segurança, remova backend/public/install.php do servidor agora. Em seguida, abra login.html e entre com o e-mail e a senha definidos. A instalação não conecta nem monitora telefones.', true);
            } catch (Throwable $e) {
                error_log('Sentinela web installer: ' . $e->getMessage());
                $error = 'A instalação não foi concluída. Confira host, porta, banco existente, usuário e permissões SQL. Detalhes técnicos foram enviados ao log do PHP.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Instalar Sentinela</title>
<style>
:root{color-scheme:light}*{box-sizing:border-box}body{margin:0;background:#f3f5f9;color:#172033;font:16px system-ui,-apple-system,Segoe UI,sans-serif;padding:22px}.wrap{max-width:820px;margin:20px auto}.card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:26px;margin:16px 0;box-shadow:0 8px 28px #0f172a08}h1{margin:0 0 8px}h2{font-size:19px;margin:0 0 16px}.muted{color:#64748b;line-height:1.6}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}label{display:block;font-weight:600;font-size:14px;margin-bottom:5px}input{width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:8px;font:inherit;margin-bottom:12px}button{background:#1d4ed8;color:#fff;border:0;border-radius:9px;padding:13px 18px;font:inherit;font-weight:700;cursor:pointer;width:100%}.error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:9px;padding:12px}.warning{background:#fff7ed;border-left:4px solid #ea580c;padding:12px 14px;line-height:1.55}@media(max-width:600px){.grid{grid-template-columns:1fr}.card{padding:18px}body{padding:12px}}
</style>
</head>
<body><main class="wrap">
<h1>Sentinela — Instalação web</h1>
<p class="muted">Assistente para domínio com PHP 8.3 e MySQL/MariaDB. Use um banco novo e credenciais dedicadas.</p>
<div class="warning"><strong>Antes de começar:</strong> configure HTTPS no domínio e crie o banco de dados e usuário pelo aaPanel. O instalador cria as tabelas e a conta inicial; não instala pacotes do sistema, não altera outros sites e não configura DNS/SSL automaticamente.</div>
<?php if ($error !== ''): ?><p class="error"><?= esc($error) ?></p><?php endif; ?>
<form method="post" autocomplete="off">
<input type="hidden" name="csrf" value="<?= esc($_SESSION['installer_csrf']) ?>">
<section class="card"><h2>1. Conexão MySQL</h2><div class="grid">
<div><label for="db_host">Host do banco</label><input id="db_host" name="db_host" value="<?= esc((string)($_POST['db_host'] ?? '127.0.0.1')) ?>" required></div>
<div><label for="db_port">Porta</label><input id="db_port" name="db_port" type="number" min="1" max="65535" value="<?= esc((string)($_POST['db_port'] ?? '3306')) ?>" required></div>
<div><label for="db_name">Nome do banco</label><input id="db_name" name="db_name" value="<?= esc((string)($_POST['db_name'] ?? '')) ?>" required></div>
<div><label for="db_user">Usuário do banco</label><input id="db_user" name="db_user" value="<?= esc((string)($_POST['db_user'] ?? '')) ?>" required></div>
</div><label for="db_pass">Senha do banco</label><input id="db_pass" name="db_pass" type="password" autocomplete="new-password" required></section>
<section class="card"><h2>2. Conta administrativa</h2>
<label for="admin_name">Nome do administrador</label><input id="admin_name" name="admin_name" value="<?= esc((string)($_POST['admin_name'] ?? '')) ?>" maxlength="120" required>
<label for="admin_email">E-mail de acesso</label><input id="admin_email" name="admin_email" type="email" value="<?= esc((string)($_POST['admin_email'] ?? '')) ?>" maxlength="190" required>
<label for="admin_password">Senha administrativa (mínimo 12 caracteres)</label><input id="admin_password" name="admin_password" type="password" minlength="12" maxlength="1024" autocomplete="new-password" required>
<p class="muted">A senha é armazenada usando hash seguro. Não reutilize a senha do banco nem a senha de outros serviços.</p></section>
<section class="card"><h2>3. Instalar</h2><p class="muted">Ao continuar, o instalador cria as tabelas do Sentinela no banco informado e registra o primeiro administrador. Não use um banco com dados de outros sistemas.</p><button type="submit">Testar banco e instalar</button></section>
</form>
<p class="muted">Depois da instalação, apague <code>backend/public/install.php</code> do servidor. O instalador também se bloqueia quando encontra a configuração ou o marcador de instalação.</p>
</main></body></html>
