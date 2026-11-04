<?php
use yii\helpers\Html;
$this->title = 'ไม่สามารถเปิดหน้านี้ได้';
?>
<h1><?= Html::encode($this->title) ?></h1><p class="mt-5 text-muted"><?= Html::encode($message) ?></p>
<p class="mt-6"><?= Html::a('กลับหน้าหลัก', ['/site/index'], ['class' => 'button-secondary']) ?></p>
