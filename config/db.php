<?php
return [
    'class' => yii\db\Connection::class,
    'dsn' => 'mysql:host=' . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ';port=' . ($_ENV['DB_PORT'] ?? '3306') . ';dbname=' . ($_ENV['DB_NAME'] ?? 'examops_dev'),
    'username' => $_ENV['DB_USER'] ?? 'examops_dev',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => 'utf8mb4',
];
