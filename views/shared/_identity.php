<?php
use app\helpers\Ui;
use yii\helpers\Html;
?>
<?php if ($includeName): ?><?= Ui::field($model, 'name', 'ชื่อและนามสกุล', 'text', ['autocomplete' => 'name', 'maxlength' => 190]) ?><?php endif ?>
<?= Ui::field($model, 'national_id', 'เลขบัตรประชาชน', 'text', ['inputmode' => 'numeric', 'pattern' => '[0-9]{13}', 'minlength' => 13, 'maxlength' => 13, 'autocomplete' => 'username']) ?>
<div>
<?= Ui::field($model, 'birth_date', 'วันเกิด 8 หลัก (พ.ศ.)', 'text', ['inputmode' => 'numeric', 'pattern' => '[0-9]{8}', 'minlength' => 8, 'maxlength' => 8, 'autocomplete' => 'off', 'aria-describedby' => 'birth-date-format']) ?>
<p id="birth-date-format" class="text-xs text-muted mt-2">ววดดปปปป ปี พ.ศ. เช่น 1 มกราคม 2540 กรอก 01012540</p>
</div>
