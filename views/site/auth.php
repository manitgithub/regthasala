<?php
use app\helpers\Ui;
use yii\helpers\Html;
$purpose = $purpose ?? 'status';
$this->title = match ($purpose) { 'ticket' => 'พิมพ์บัตรประจำตัวสอบ', 'results' => 'ผลสอบ', default => 'ตรวจสอบสถานะ' };
?>
<div class="auth-form"><div class="auth-brand"><img class="school-crest" src="/images/thasala-crest.png" alt="ตราโรงเรียน" width="56" height="64"><p><?= Html::encode(Yii::$app->params['schoolName']) ?><small><?= Html::encode(Yii::$app->params['systemName']) ?></small></p></div><nav class="applicant-service-tabs" aria-label="บริการผู้สมัคร"><?= Html::a('ตรวจสอบสถานะ', ['status'], ['aria-current' => $purpose === 'status' ? 'page' : null]) ?><?= Html::a('พิมพ์บัตรสอบ', ['exam-card'], ['aria-current' => $purpose === 'ticket' ? 'page' : null]) ?><?= Html::a('ผลสอบ', ['results'], ['aria-current' => $purpose === 'results' ? 'page' : null]) ?></nav><h1><?= Html::encode($this->title) ?></h1>
<?php if ($error): ?><div class="alert alert-error mt-6" role="alert"><?= Html::encode($error) ?></div><?php endif ?>
<?= Html::beginForm('', 'post', ['class' => 'grid gap-5 mt-8']) ?>
<?= $this->render('../shared/_identity', ['model' => $model, 'includeName' => false]) ?>
<button class="button-primary justify-center" type="submit"><?= Ui::icon($purpose === 'ticket' ? 'printer' : 'file-check-2') ?><?= match ($purpose) { 'ticket' => 'ดูบัตรประจำตัวสอบ', 'results' => 'ดูผลสอบ', default => 'ตรวจสอบสถานะ' } ?></button>
<?= Html::endForm() ?>
<div class="mt-6 text-sm"><?= Html::a('สมัครสอบ', ['index'], ['class' => 'text-accent font-medium']) ?></div>
<div class="mt-5 text-xs text-muted"><?= Html::a('เข้าสู่ระบบสำหรับเจ้าหน้าที่', ['staff-login'], ['class' => 'hover:text-accent']) ?></div>
</div>
