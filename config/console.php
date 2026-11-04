<?php
return [
    'id' => 'examops-console', 'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'app\\commands', 'timeZone' => 'Asia/Bangkok',
    'components' => ['db' => require __DIR__ . '/db.php'],
];
