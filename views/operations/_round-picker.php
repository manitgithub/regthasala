<?php
use yii\helpers\{Html, ArrayHelper};
use app\helpers\Ui;
?>
<?= Html::beginForm('', 'get', ['class' => 'round-picker']) ?><label class="field"><span>รอบสอบ</span><?= Html::dropDownList('round', $round?->id, ArrayHelper::map($rounds, 'id', 'name'), ['class' => 'input', 'aria-label' => 'รอบสอบ']) ?></label><button type="submit" class="button-secondary"><?= Ui::icon('search') ?>แสดงข้อมูล</button><?= Html::endForm() ?>
