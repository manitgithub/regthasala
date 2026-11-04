<?php
$cookieKey = $_ENV['APP_COOKIE_KEY'] ?? '';
if (strlen($cookieKey) < 32) {
    throw new RuntimeException('Set APP_COOKIE_KEY with at least 32 random characters in .env');
}
return [
    'id' => 'examops', 'name' => 'ระบบรับสมัครและจัดสอบ โรงเรียนท่าศาลาประสิทธิ์ศึกษา', 'basePath' => dirname(__DIR__),
    'params' => ['schoolName' => 'โรงเรียนท่าศาลาประสิทธิ์ศึกษา', 'systemName' => 'ระบบรับสมัครและจัดสอบ'],
    'language' => 'th-TH', 'timeZone' => 'Asia/Bangkok',
    'components' => [
        'cache' => ['class' => yii\caching\FileCache::class],
        'db' => require __DIR__ . '/db.php',
        'request' => ['cookieValidationKey' => $cookieKey],
        'user' => ['identityClass' => app\models\User::class, 'enableAutoLogin' => false, 'loginUrl' => ['site/status']],
        'session' => ['name' => 'examops_session', 'cookieParams' => ['httpOnly' => true, 'sameSite' => 'Lax']],
        'errorHandler' => ['errorAction' => 'site/error'],
        'log' => ['targets' => [['class' => yii\log\FileTarget::class, 'levels' => ['error', 'warning']]]],
        'urlManager' => ['enablePrettyUrl' => true, 'showScriptName' => false, 'rules' => []],
        'assetManager' => ['bundles' => ['yii\\web\\JqueryAsset' => false, 'yii\\web\\YiiAsset' => false]],
    ],
];
