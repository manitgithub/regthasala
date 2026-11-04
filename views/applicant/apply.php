<?php
use app\helpers\Ui;
use yii\helpers\Html;
$this->title = 'สมัคร ' . $round->name;
$admission = $admission ?? null;
$preview = $preview ?? false;
$payment = $payment ?? new \app\models\PaymentForm();
?>
<?= Ui::link('รอบสอบทั้งหมด', ['/site/index'], 'arrow-left', 'back-link') ?>
<div class="page-heading"><div><p class="eyebrow"><?= $admission ? 'ใบสมัครเข้าศึกษา · ห้องเรียนพิเศษ' : 'เลือกวิชาสอบ' ?></p><h1><?= Html::encode($round->name) ?></h1><p class="subheading">ปิดรับสมัคร <?= Ui::date($round->closes_at) ?></p></div></div>
<?= $this->render('../shared/_schedule', ['round' => $round, 'sectionClass' => 'mb-8']) ?>
<?php if ($preview): ?><div class="alert" role="status">ตัวอย่างจากประกาศเดิม ปีการศึกษา 2569 · ไม่เปิดรับสมัครจริง</div><?php endif ?>
<?php if ($error): ?><div class="alert alert-error" role="alert"><?= Html::encode($error) ?></div><?php endif ?>
<?php if ($admission && $admission->hasErrors()): ?><div class="alert alert-error" role="alert"><?= Html::errorSummary($admission, ['header' => 'กรุณาตรวจสอบข้อมูล']) ?></div><?php endif ?>
<?= Html::beginForm('', 'post', ['id' => 'application-form', 'enctype' => 'multipart/form-data']) ?>
<?php if ($admission): ?>
<fieldset class="program-selector" id="admission-program" aria-describedby="program-requirements"><legend>ระดับชั้นและโครงการที่สมัคร</legend><div class="program-cards">
<?php foreach ($round->programs as $program): if ($program->status !== 'open') continue; ?>
<label class="program-card"><input type="radio" name="AdmissionForm[program_id]" value="<?= $program->id ?>" required <?= (int) $admission->program_id === (int) $program->id ? 'checked' : '' ?> data-fee="<?= Html::encode($program->application_fee) ?>" data-gpa-basis="<?= Html::encode($program->gpa_basis) ?>" data-min-gpa="<?= $program->minimum_gpa ?>" data-min-foreign="<?= $program->minimum_foreign_gpa ?>" data-intake="<?= $program->intake ?>" data-enrollment="<?= $program->enrollment_fee ?>"><span class="program-card-content"><strong><?= $program->gradeLabel ?> · <?= Html::encode($program->code) ?></strong><span><?= Html::encode($program->name) ?></span><small>รับ <?= (int) $program->intake ?> คน · เกรดขั้นต่ำ <?= Html::encode($program->minimum_gpa) ?></small><small>ค่าสมัคร <?= Ui::money($program->application_fee) ?> บาท</small></span></label>
<?php endforeach ?></div><?= Html::error($admission, 'program_id', ['class' => 'field-error']) ?><p class="text-xs text-muted leading-6" id="program-requirements" aria-live="polite">เกณฑ์คุณสมบัติและวิชาสอบจะแสดงตามโครงการที่เลือก</p></fieldset>
<div class="admission-layout"><div class="min-w-0"><?= $this->render('_admission', compact('admission', 'identity', 'preview')) ?>
<?php elseif (Yii::$app->user->isGuest): ?>
<section class="form-section mb-8"><h2>ข้อมูลผู้สมัคร</h2><div class="field-grid max-w-3xl">
<?= $this->render('../shared/_identity', ['model' => $identity, 'includeName' => true]) ?>
</div></section>
<?php endif ?>
<?php if (!$admission): ?><div class="round-layout"><div><?php endif ?><div class="section-bar <?= $admission ? 'mt-8' : '' ?>"><h2><?= $admission ? 'ชุดวิชาสอบของโครงการ' : 'รายวิชาที่เปิดรับสมัคร' ?></h2></div>
<?php foreach ($round->subjects as $subject): $remaining = $subject->capacity - $subject->getReservedCount(); $disabled = !$round->isOpen() || $subject->status !== 'open' || $remaining <= 0; ?>
<?php if ($admission): $programIds = []; foreach ($round->programs as $program) foreach ($program->subjects as $linked) if ($linked->id === $subject->id) $programIds[] = $program->id; ?>
<div class="subject-choice" data-admission-subject data-program-ids="<?= implode(',', $programIds) ?>" hidden><?= Ui::icon('book-open') ?><span><strong><?= Html::encode($subject->name) ?></strong><small><?= Ui::date($subject->starts_at) ?>–<?= date('H:i', strtotime($subject->ends_at)) ?></small></span><small>วิชาบังคับ</small></div>
<?php else: ?>
<label class="subject-choice"><input type="checkbox" name="subjects[]" value="<?= $subject->id ?>" data-fee="<?= Html::encode($subject->fee) ?>" <?= $disabled ? 'disabled' : '' ?> <?= in_array((string) $subject->id, (array) Yii::$app->request->post('subjects', []), true) ? 'checked' : '' ?>><span class="min-w-0"><strong><?= Html::encode($subject->name) ?></strong><small><?= Html::encode($subject->code) ?> · <?= Ui::date($subject->starts_at) ?>–<?= date('H:i', strtotime($subject->ends_at)) ?></small><small><?= Html::encode($subject->venue ?: 'รอประกาศสถานที่') ?> · <?= $disabled ? 'ไม่เปิดรับสมัคร' : 'เหลือ ' . $remaining . ' ที่นั่ง' ?></small></span><b><?= Ui::money($subject->fee) ?><small>บาท</small></b></label>
<?php endif ?>
<?php endforeach ?>
<?= $this->render('_payment', compact('round', 'payment')) ?>
</div><aside class="application-summary"><h2>สรุปค่าสมัคร</h2><div class="flex justify-between my-6 text-sm"><span class="text-muted">จำนวนรายวิชา</span><span id="selected-count">0 วิชา</span></div><div class="summary-total"><span><?= $admission ? 'ค่าระเบียบ / ค่าสมัครโครงการ' : 'ยอดรวม' ?></span><strong><span id="selected-total">0.00</span><small>บาท</small></strong></div>
<?php if ($admission): ?><label class="flex gap-3 items-start text-xs leading-6 mt-6"><?= Html::activeCheckbox($admission, 'confirmed', ['label' => false, 'required' => true, 'class' => 'mt-1 shrink-0']) ?><span>ข้าพเจ้าตรวจสอบและรับรองว่าข้อมูลและเอกสารที่ส่งถูกต้องตรงตามความเป็นจริง</span></label><?= Html::error($admission, 'confirmed', ['class' => 'field-error']) ?><p id="enrollment-fee" class="text-xs text-muted mt-4"></p><?php endif ?>
<button class="button-primary w-full justify-center mt-6" type="submit" <?= !$round->isOpen() || $preview || !$round->hasPaymentAccount() ? 'disabled' : '' ?>><?= Ui::icon('check') ?><?= $preview ? 'ตัวอย่างใบสมัคร' : 'ยืนยันใบสมัคร' ?></button><p class="text-xs text-muted mt-4">ส่งใบสมัครพร้อมสลิปเพื่อรอตรวจสอบการชำระเงิน</p><p class="text-xs text-muted mt-4">ครบกำหนดชำระ <?= Ui::date($round->payment_due_at) ?></p></aside></div>
<?= Html::endForm() ?>
