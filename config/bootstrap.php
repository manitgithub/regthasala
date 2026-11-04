<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
defined('YII_DEBUG') or define('YII_DEBUG', ($_ENV['APP_DEBUG'] ?? '0') === '1');
defined('YII_ENV') or define('YII_ENV', $_ENV['APP_ENV'] ?? 'prod');
require dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';
Yii::setAlias('@app', dirname(__DIR__));
