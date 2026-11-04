<?php
use app\helpers\Ui;
use yii\helpers\Html;
$ticketForm = $ticketForm ?? $application->ticket ?? new \app\models\ExamTicket(['published' => false]);
$issuedNumber = \app\models\ExamTicket::find()->select('exam_number')->where(['application_id' => $application->id])->scalar();
?>
<section class="form-section mt-10" id="ticket-settings"><div class="section-bar"><h2>บัตรประจำตัวสอบ</h2><span class="badge <?= $ticketForm->published ? 'badge-green' : 'badge-neutral' ?>"><?= $ticketForm->published ? 'เปิดพิมพ์แล้ว' : 'ยังไม่เปิดพิมพ์' ?></span></div>
<?= Html::beginForm(['/admin/save-ticket', 'id' => $application->id], 'post') ?>
<?php if ($ticketForm->hasErrors()): ?><div class="alert alert-error" role="alert"><?= Html::errorSummary($ticketForm, ['header' => 'กรุณาตรวจสอบข้อมูลบัตรสอบ']) ?></div><?php endif ?>
<div class="field-grid">
<div class="field"><span class="text-sm font-medium text-muted">เลขประจำตัวสอบ</span><p class="text-base leading-7 font-medium"><?= Html::encode($issuedNumber ?: 'ยังไม่ออกเลขสอบ') ?></p><?= Ui::link('ตั้งค่าการรันเลข', ['/operations/rooms', 'round' => $application->round_id, '#' => 'exam-number-settings'], 'settings-2', 'button-quiet') ?></div>
<?= Ui::field($ticketForm, 'venue', 'สนามสอบ / อาคาร', 'text', ['maxlength' => 190, 'required' => false]) ?>
<?= Ui::field($ticketForm, 'room', 'ห้องสอบ', 'text', ['maxlength' => 100, 'required' => false]) ?>
<?= Ui::field($ticketForm, 'seat', 'เลขที่นั่งสอบ', 'text', ['maxlength' => 50, 'required' => false]) ?>
<div class="col-span-full"><?= Ui::field($ticketForm, 'note', 'ข้อปฏิบัติ / หมายเหตุ', 'textarea', ['maxlength' => 2000, 'rows' => 3]) ?></div>
</div><label class="flex items-start gap-3 mt-6 text-sm"><?= Html::activeCheckbox($ticketForm, 'published', ['label' => false, 'class' => 'mt-1']) ?><span>เปิดให้ผู้สมัครพิมพ์บัตรประจำตัวสอบ</span></label><p class="text-sm text-muted mt-3">เปิดพิมพ์เมื่อเจ้าหน้าที่ตรวจสอบคุณสมบัติและข้อมูลการสอบเรียบร้อยแล้ว</p>
<div class="form-footer"><?php if ($application->ticket && $application->ticket->published): ?><?= Ui::link('ดูบัตรสอบ', ['/applicant/ticket', 'id' => $application->id], 'printer') ?><?php endif ?><button class="button-primary" type="submit"><?= Ui::icon('check') ?>บันทึกบัตรสอบ</button></div>
<?= Html::endForm() ?></section>
