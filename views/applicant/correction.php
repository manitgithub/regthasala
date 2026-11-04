<?php
use app\helpers\Ui;
use yii\helpers\Html;
$this->title = 'แก้ไขใบสมัคร';
?>
<?= Ui::link('กลับใบสมัคร', ['view', 'id' => $application->id], 'arrow-left', 'back-link') ?><div class="page-heading"><div><p class="eyebrow"><?= Html::encode($application->reference) ?></p><h1>แก้ไขใบสมัคร</h1></div></div>
<?php if ($error): ?><div class="alert alert-error"><?= Html::encode($error) ?></div><?php endif ?>
<?= Html::beginForm('', 'post', ['enctype' => 'multipart/form-data']) ?><?= Html::errorSummary($admission, ['class' => 'field-error']) ?><div class="field-grid">
<?php foreach ($admission->getProfileData() as $attribute => $value): ?>
<?php if (in_array($attribute, ['subdistrict_code', 'subdistrict', 'district', 'province', 'postal_code'], true)) continue; ?>
<?php if ($attribute === 'prefix'): ?><?= Ui::select($admission, $attribute, $admission->getAttributeLabel($attribute), array_combine(['เด็กชาย', 'เด็กหญิง', 'นาย', 'นางสาว', 'อื่น ๆ'], ['เด็กชาย', 'เด็กหญิง', 'นาย', 'นางสาว', 'อื่น ๆ'])) ?>
<?php elseif ($attribute === 'education_level'): ?><?= Ui::select($admission, $attribute, $admission->getAttributeLabel($attribute), \app\models\AdmissionForm::LEVELS) ?>
<?php elseif ($attribute === 'education_status'): ?><?= Ui::select($admission, $attribute, $admission->getAttributeLabel($attribute), ['studying' => 'กำลังศึกษา', 'graduated' => 'สำเร็จการศึกษาแล้ว']) ?>
<?php else: ?><?= Ui::field($admission, $attribute, $admission->getAttributeLabel($attribute), $attribute === 'email' ? 'email' : 'text') ?><?php endif ?>
<?php endforeach ?><?= $this->render('../shared/_thai-address', ['model' => $admission]) ?></div>
<div class="field mt-6"><label for="correction-photo">รูปถ่ายใหม่ (เมื่อเจ้าหน้าที่ขอแก้ไข)</label><?= Html::activeFileInput($admission, 'photo', ['id' => 'correction-photo', 'class' => 'upload-input', 'accept' => '.jpg,.jpeg,.png']) ?></div>
<?php if ($application->payment?->status === 'rejected'): ?><?= $this->render('_payment', ['round' => $application->round, 'payment' => $payment]) ?><?php endif ?>
<label class="flex gap-3 items-center mt-6"><?= Html::activeCheckbox($admission, 'confirmed', ['label' => false, 'required' => true]) ?>รับรองความถูกต้องของข้อมูลที่แก้ไข</label><div class="form-footer"><button type="submit" class="button-primary">ส่งข้อมูลแก้ไข</button></div><?= Html::endForm() ?>
