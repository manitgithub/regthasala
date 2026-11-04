<?php
require dirname(__DIR__) . '/config/bootstrap.php';
(new yii\web\Application(require dirname(__DIR__) . '/config/web.php'))->run();
