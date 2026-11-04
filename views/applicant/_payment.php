<?php
use app\helpers\Ui;
use yii\helpers\Html;
?>
<section class="form-section mt-8" id="payment-details" aria-labelledby="payment-heading">
<h2 id="payment-heading">ชำระค่าสมัครและแนบสลิป</h2>
<?php if ($round->hasPaymentAccount()): ?>
<dl class="payment-account">
<div><dt>ธนาคาร</dt><dd><?= Html::encode($round->payment_bank) ?></dd></div>
<div><dt>เลขบัญชี</dt><dd class="font-semibold tabular-nums"><?= Html::encode($round->payment_account) ?></dd></div>
<div><dt>ชื่อบัญชี</dt><dd><?= Html::encode($round->payment_account_name) ?></dd></div>
<div><dt>ยอดที่ต้องชำระ</dt><dd><strong id="payment-total">0.00</strong> บาท</dd></div>
</dl>
<?php if ($round->payment_instructions): ?><p class="text-sm whitespace-pre-line mt-4"><?= Html::encode($round->payment_instructions) ?></p><?php endif ?>
<p class="text-sm text-muted my-5">ตรวจสอบโครงการและบัญชีผู้รับก่อนโอนเงิน ต้องกรอกข้อมูลการโอนและแนบสลิปให้ครบเพื่อส่งตรวจสอบพร้อมใบสมัคร</p>
<?php if ($payment->hasErrors()): ?><div class="alert alert-error" role="alert"><?= Html::errorSummary($payment, ['header' => 'กรุณาตรวจสอบข้อมูลการชำระเงิน']) ?></div><?php endif ?>
<div class="field-grid">
<?= Ui::field($payment, 'amount', 'ยอดเงินที่โอน (บาท)', 'number', ['min' => '0.01', 'max' => '99999999.99', 'step' => '0.01']) ?>
<?= Ui::field($payment, 'transferred_at', 'วันและเวลาโอน', 'datetime-local') ?>
<?= Ui::field($payment, 'transfer_reference', 'เลขอ้างอิงการโอน (ถ้ามี)', 'text', ['maxlength' => 190]) ?>
<div class="field"><label for="paymentform-slip">หลักฐานการโอนเงิน</label><?= Html::activeFileInput($payment, 'slip', ['id' => 'paymentform-slip', 'class' => 'upload-input', 'accept' => '.jpg,.jpeg,.png,.pdf', 'required' => true]) ?><p class="text-xs text-muted">JPG / PNG / PDF ขนาดไม่เกิน 5 MB</p><?= Html::error($payment, 'slip', ['class' => 'field-error']) ?></div>
</div><p class="text-xs text-muted mt-4">สลิปที่ส่งจะอยู่ระหว่างตรวจสอบ ยังไม่ถือว่ายืนยันการชำระเงินแล้ว</p>
<?php else: ?><p class="text-sm text-muted">โรงเรียนยังไม่ระบุบัญชีรับเงินสำหรับรอบสอบนี้ จึงยังไม่สามารถยืนยันใบสมัครได้ กรุณารอโรงเรียนกำหนดบัญชีก่อนโอนเงิน</p><?php endif ?>
</section>
