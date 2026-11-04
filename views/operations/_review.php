<?php
use app\helpers\Ui;
use app\services\ReviewService;
use yii\helpers\Html;
$actor = Yii::$app->user->identity;
$revision = ReviewService::revision($application);
$labels = \app\models\AdmissionForm::DOCUMENTS;
?>
<section class="form-section mt-8" id="review-section"><h2>ตรวจสอบคุณสมบัติและเอกสาร</h2><div class="review-overview"><span>คุณสมบัติ <?= Ui::status($application->qualification_status) ?></span><span>การชำระเงิน <?= $application->payment ? Ui::status($application->payment->status) : Ui::status('incomplete') ?></span></div>
<?php if ($application->qualification_reason): ?><p class="text-sm mb-5">หมายเหตุคุณสมบัติ: <?= Html::encode($application->qualification_reason) ?></p><?php endif ?>
<?php foreach ($application->currentDocuments as $document): if (!$actor->canViewDocument($document->kind) || $document->kind === 'payment_slip') continue; ?>
<div class="review-document"><div class="section-bar"><strong><?= Html::encode($labels[$document->kind] ?? $document->kind) ?></strong><?= Ui::status($document->review_status) ?><?= Ui::link('เปิดเอกสาร', ['/applicant/document', 'id' => $document->id], 'arrow-down-to-line') ?></div>
<?php if ($document->review_reason): ?><p class="text-sm mb-4"><?= Html::encode($document->review_reason) ?></p><?php endif ?>
<?php if ($actor->can('documents.review')): ?><?= Html::beginForm(['/operations/review', 'id' => $application->id], 'post', ['class' => 'review-form']) ?><?= Html::hiddenInput('kind', 'document') ?><?= Html::hiddenInput('document', $document->id) ?><?= Html::hiddenInput('revision', $revision) ?><label class="field">ผลตรวจเอกสาร<?= Html::dropDownList('decision', '', ['accepted' => 'เอกสารถูกต้อง', 'rejected' => 'ให้แก้ไขเอกสาร'], ['class' => 'input', 'aria-label' => 'ผลตรวจเอกสาร']) ?></label><label class="field">เหตุผล / หมายเหตุ<?= Html::textInput('reason', '', ['class' => 'input', 'maxlength' => 2000]) ?></label><button type="submit" class="button-primary">บันทึกผลเอกสาร</button><?= Html::endForm() ?><?php endif ?></div><?php endforeach ?>
<?php foreach (['qualification' => ['qualification.review', 'พิจารณาคุณสมบัติ', ['approved' => 'อนุมัติคุณสมบัติ', 'correction' => 'ส่งกลับให้แก้ไข', 'rejected' => 'ไม่อนุมัติ']], 'payment' => ['payments.review', 'ตรวจรับชำระเงิน', ['verified' => 'ตรวจรับชำระเงินแล้ว', 'rejected' => 'ไม่รับสลิป / ให้แก้ไข']]] as $kind => [$permission, $heading, $options]): if (!$actor->can($permission) || $kind === 'payment' && !$application->payment) continue; ?>
<div class="review-document"><h3 class="font-semibold mb-4"><?= $heading ?></h3><?= Html::beginForm(['/operations/review', 'id' => $application->id], 'post', ['class' => 'review-form']) ?><?= Html::hiddenInput('kind', $kind) ?><?= Html::hiddenInput('revision', $revision) ?><label class="field">ผลพิจารณา<?= Html::dropDownList('decision', '', $options, ['class' => 'input', 'aria-label' => $heading]) ?></label><label class="field">เหตุผล / หมายเหตุ<?= Html::textInput('reason', '', ['class' => 'input', 'maxlength' => 2000]) ?></label><button type="submit" class="button-primary">บันทึก<?= $heading ?></button><?= Html::endForm() ?></div>
<?php endforeach ?></section>
