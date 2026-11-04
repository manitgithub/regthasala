<?php
// Local development bootstrap. Credentials are generated and only written to ignored files.
if (PHP_SAPI !== 'cli') exit(1);
$root = dirname(__DIR__);
if (is_file($root . '/.env')) { fwrite(STDOUT, ".env exists; left unchanged. Run php yii migrate.\n"); exit(0); }
$pdo = new PDO(getenv('SETUP_MYSQL_DSN') ?: 'mysql:host=127.0.0.1;port=3306;charset=utf8mb4', getenv('SETUP_MYSQL_USER') ?: 'root', getenv('SETUP_MYSQL_PASSWORD') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
if ($pdo->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='examops_dev'")->fetch()) {
    fwrite(STDERR, "examops_dev already exists. Configure .env from .env.example to use it.\n"); exit(1);
}
$password = bin2hex(random_bytes(24));
$cookie = bin2hex(random_bytes(32));
$pdo->exec('CREATE DATABASE examops_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec("CREATE USER 'examops_dev'@'localhost' IDENTIFIED BY " . $pdo->quote($password));
$pdo->exec("GRANT ALL PRIVILEGES ON examops_dev.* TO 'examops_dev'@'localhost'");
file_put_contents($root . '/.env', "APP_ENV=dev\nAPP_DEBUG=0\nAPP_COOKIE_KEY=$cookie\nDB_HOST=127.0.0.1\nDB_PORT=3306\nDB_NAME=examops_dev\nDB_USER=examops_dev\nDB_PASSWORD=$password\n");
chmod($root . '/.env', 0600);
foreach (['runtime', 'web/assets'] as $directory) if (!is_dir($root . '/' . $directory)) mkdir($root . '/' . $directory, 0755, true);
fwrite(STDOUT, "Created local examops_dev database, scoped database user and .env.\n");
