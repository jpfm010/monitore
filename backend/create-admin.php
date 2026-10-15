<?php
declare(strict_types=1);

/**
 * Criar primeiro usuário com segurança a partir do terminal:
 * php backend/create-admin.php
 * Nunca execute este arquivo via navegador; ele falha fora do CLI.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Not found\n");
}
$configPath = __DIR__ . '/config.php';
if (!is_file($configPath)) {
    fwrite(STDERR, "Configuração ausente. Copie config.example.php para config.php e configure o banco.\n");
    exit(1);
}
$config = require $configPath;
$name = trim((string)readline('Nome do administrador: '));
$email = filter_var(trim((string)readline('E-mail do administrador: ')), FILTER_VALIDATE_EMAIL);
if ($name === '' || mb_strlen($name) > 120 || !$email) {
    fwrite(STDERR, "Nome ou e-mail inválido.\n");
    exit(1);
}
fwrite(STDOUT, "Senha (mínimo 12 caracteres): ");
if (function_exists('shell_exec') && DIRECTORY_SEPARATOR === '/') {
    shell_exec('stty -echo');
}
$password = (string)fgets(STDIN);
if (function_exists('shell_exec') && DIRECTORY_SEPARATOR === '/') {
    shell_exec('stty echo');
    fwrite(STDOUT, "\n");
}
$password = rtrim($password, "\r\n");
if (strlen($password) < 12 || strlen($password) > 1024) {
    fwrite(STDERR, "A senha precisa ter entre 12 e 1024 caracteres.\n");
    exit(1);
}
try {
    $db = $config['db'];
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $db['host'], $db['port'], $db['name'], $db['charset'] ?? 'utf8mb4');
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count > 0) {
        fwrite(STDERR, "Já existem usuários cadastrados. Por segurança, este script só cria a primeira conta.\n");
        exit(1);
    }
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, active) VALUES (?, ?, ?, 'admin', 1)");
    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    fwrite(STDOUT, "Administrador criado. Remova ou mantenha este script somente fora do diretório público.\n");
} catch (Throwable $e) {
    fwrite(STDERR, "Falha ao criar administrador: " . $e->getMessage() . "\n");
    exit(1);
}
