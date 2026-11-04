<?php
use app\helpers\Ui;
use yii\helpers\Html;
$this->title = 'เข้าสู่ระบบเจ้าหน้าที่';
?>
<div class="auth-form"><div class="auth-brand"><img class="school-crest" src="/images/thasala-crest.png" alt="ตราโรงเรียน" width="56" height="64"><p><?= Html::encode(Yii::$app->params['schoolName']) ?><small><?= Html::encode(Yii::$app->params['systemName']) ?></small></p></div><h1><?= Html::encode($this->title) ?></h1>
<?php if ($error): ?><div class="alert alert-error mt-6" role="alert"><?= Html::encode($error) ?></div><?php endif ?>
<?= Html::beginForm('', 'post', ['class' => 'grid gap-5 mt-8']) ?>
<div class="field"><label for="email">อีเมล</label><input class="input" type="email" name="email" id="email" required maxlength="190" autocomplete="username"></div>
<div class="field"><label for="password">รหัสผ่าน</label><input class="input" type="password" name="password" id="password" required autocomplete="current-password"></div>
<button class="button-primary justify-center" type="submit"><?= Ui::icon('log-in') ?>เข้าสู่ระบบ</button>
<?= Html::endForm() ?>
<div class="mt-6 text-sm text-muted"><?= Html::a('เข้าสู่ระบบผู้สมัครสอบ', ['login'], ['class' => 'text-accent']) ?></div>
</div>
