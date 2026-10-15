<?php
declare(strict_types=1);

/**
 * API inicial do Sentinela.
 * Apenas gestão de conta, consentimento, dispositivos e eventos demonstrativos.
 * Não acessa WhatsApp, notificações, GPS ou conteúdo privado de terceiros.
 */
$configPath = dirname(__DIR__) . '/config.php';
if (!is_file($configPath)) {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Configuração ausente. Copie config.example.php para config.php e configure o banco.']);
    exit;
}
$config = require $configPath;
session_name($config['session_name'] ?? 'sentinela_session');
session_set_cookie_params([
    'httponly' => true,
    'secure' => (bool)($config['secure_cookies'] ?? true),
    'samesite' => 'Strict',
    'path' => '/',
]);
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Frame-Options: DENY');

function respond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function bodyJson(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    if (!is_array($data)) respond(400, ['error' => 'JSON inválido.']);
    return $data;
}
function requireCsrf(): void {
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $known = $_SESSION['csrf'] ?? '';
    if (!$known || !$sent || !hash_equals($known, $sent)) respond(419, ['error' => 'Token de segurança inválido. Atualize a sessão.']);
}
function requireUser(): int {
    if (empty($_SESSION['user_id'])) respond(401, ['error' => 'Autenticação necessária.']);
    return (int)$_SESSION['user_id'];
}
function audit(PDO $pdo, ?int $userId, string $action, ?string $targetType = null, ?int $targetId = null): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ipHash = $ip !== '' ? hash('sha256', $ip . (getenv('SENTINELA_AUDIT_SALT') ?: 'configure-um-salt-no-servidor')) : null;
    $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, target_type, target_id, ip_hash) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $action, $targetType, $targetId, $ipHash]);
}
try {
    $db = $config['db'];
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $db['host'], $db['port'], $db['name'], $db['charset'] ?? 'utf8mb4');
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $e) {
    error_log('Sentinela database connection failed: ' . $e->getMessage());
    respond(503, ['error' => 'Banco de dados indisponível. Verifique os logs do servidor.']);
}

$action = (string)($_GET['action'] ?? '');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($action === 'csrf' && $method === 'GET') {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    respond(200, ['csrf' => $_SESSION['csrf']]);
}

if ($action === 'login' && $method === 'POST') {
    $input = bodyJson();
    $email = filter_var(trim((string)($input['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $password = (string)($input['password'] ?? '');
    if (!$email || $password === '' || strlen($password) > 1024) respond(422, ['error' => 'Informe e-mail e senha válidos.']);
    $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? AND active = 1 LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        usleep(250000);
        respond(401, ['error' => 'E-mail ou senha incorretos.']);
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    audit($pdo, (int)$user['id'], 'auth.login');
    unset($user['password_hash']);
    respond(200, ['user' => $user, 'csrf' => $_SESSION['csrf']]);
}

if ($action === 'logout' && $method === 'POST') {
    requireUser(); requireCsrf();
    audit($pdo, (int)$_SESSION['user_id'], 'auth.logout');
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => $p['httponly'], 'samesite' => $p['samesite'] ?? 'Strict']);
    }
    session_destroy();
    respond(200, ['ok' => true]);
}

if ($action === 'health' && $method === 'GET') respond(200, ['ok' => true, 'service' => 'sentinela-api', 'mode' => 'initial']);

$userId = requireUser();
if ($action === 'me' && $method === 'GET') {
    $stmt = $pdo->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ? AND active = 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) respond(401, ['error' => 'Conta indisponível.']);
    respond(200, ['user' => $user, 'csrf' => $_SESSION['csrf'] ?? null]);
}

if ($action === 'devices' && $method === 'GET') {
    $stmt = $pdo->prepare('SELECT id, label, platform, status, created_at, updated_at FROM devices WHERE owner_user_id = ? ORDER BY id DESC');
    $stmt->execute([$userId]);
    respond(200, ['devices' => $stmt->fetchAll()]);
}

if ($action === 'devices' && $method === 'POST') {
    requireCsrf();
    $input = bodyJson();
    $label = trim((string)($input['label'] ?? ''));
    $platform = trim((string)($input['platform'] ?? ''));
    $confirmed = ($input['consent_confirmed'] ?? false) === true;
    $scope = trim((string)($input['consent_scope'] ?? ''));
    if (mb_strlen($label) < 2 || mb_strlen($label) > 120 || mb_strlen($platform) < 2 || mb_strlen($platform) > 80) {
        respond(422, ['error' => 'Nome e plataforma devem ter tamanho válido.']);
    }
    if (!$confirmed || mb_strlen($scope) < 10 || mb_strlen($scope) > 500) {
        respond(422, ['error' => 'Confirme a autorização válida e descreva o escopo do consentimento.']);
    }
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO devices (owner_user_id, label, platform, status) VALUES (?, ?, ?, 'authorized')");
    $stmt->execute([$userId, $label, $platform]);
    $deviceId = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare('INSERT INTO consent_records (device_id, recorded_by_user_id, consent_scope, consent_version) VALUES (?, ?, ?, ?)');
    $stmt->execute([$deviceId, $userId, $scope, '1.0']);
    audit($pdo, $userId, 'device.register', 'device', $deviceId);
    $pdo->commit();
    respond(201, ['device' => ['id' => $deviceId, 'label' => $label, 'platform' => $platform, 'status' => 'authorized'], 'notice' => 'Cadastro administrativo salvo. Nenhum telefone foi conectado nem monitorado.']);
}

if ($action === 'events' && $method === 'GET') {
    $deviceId = filter_input(INPUT_GET, 'device_id', FILTER_VALIDATE_INT);
    $stmt = $pdo->prepare('SELECT id, label FROM devices WHERE owner_user_id = ? AND id = ?');
    $stmt->execute([$userId, $deviceId ?: 0]);
    if (!$stmt->fetch()) respond(404, ['error' => 'Dispositivo não encontrado.']);
    $stmt = $pdo->prepare("SELECT id, event_type, summary, source, occurred_at FROM activity_events WHERE device_id = ? AND source = 'demo' ORDER BY occurred_at DESC LIMIT 100");
    $stmt->execute([$deviceId]);
    respond(200, ['events' => $stmt->fetchAll(), 'notice' => 'Somente eventos demonstrativos são retornados nesta versão.']);
}

respond(404, ['error' => 'Rota não encontrada.']);
