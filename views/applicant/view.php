<?php
use app\helpers\Ui;
use app\models\AdmissionForm;
use yii\helpers\Html;
$this->title = $application->reference;
$snapshot = $application->admission_snapshot;
?>
<?= Ui::link('รายการใบสมัคร', Yii::$app->user->identity->isStaff() ? ['/admin/applications'] : ['index'], 'arrow-left', 'back-link') ?>
<div class="page-heading"><div><p class="eyebrow"><?= Html::encode($application->reference) ?></p><h1><?= Html::encode($application->round->name) ?></h1><?php if ($snapshot): ?><p class="subheading"><?= Html::encode($snapshot['code'] . ' · ' . $snapshot['name']) ?></p><?php endif ?></div><?= Ui::status($application->status) ?></div>
<?php if ($application->profile): $profile = $application->profile->data; $form = new AdmissionForm(); ?>
<h2 class="mb-5">ข้อมูลผู้สมัคร</h2><dl class="profile-grid">
<?php foreach ($profile as $attribute => $value):
    if ($attribute === 'subdistrict_code') continue;
    if ($attribute === 'education_level') $value = AdmissionForm::LEVELS[$value] ?? $value;
    if ($attribute === 'education_status') $value = $value === 'studying' ? 'กำลังศึกษา' : 'สำเร็จการศึกษาแล้ว'; ?>
<div><dt><?= Html::encode($form->getAttributeLabel($attribute)) ?></dt><dd><?= Html::encode((string) ($value ?? '')) ?: '—' ?></dd></div>
<?php endforeach ?></dl>
<h2 class="mt-8 mb-4">เอกสารประกอบการสมัคร</h2><div class="document-list"><?php foreach ($application->currentDocuments as $document): if ($document->kind === 'payment_slip' || Yii::$app->user->identity->isStaff() && !Yii::$app->user->identity->canViewDocument($document->kind)) continue; ?><div><?= Ui::icon('file-text') ?><span><?= Html::encode(AdmissionForm::DOCUMENTS[$document->kind] ?? $document->kind) ?></span><?= Ui::status($document->review_status) ?><?= Ui::link('ดาวน์โหลด', ['document', 'id' => $document->id], 'arrow-down-to-line', 'button-quiet ml-auto') ?></div><?php endforeach ?></div>
<?php endif ?>
<?php if ($application->payment): $payment = $application->payment; ?><section class="form-section mt-8"><h2>หลักฐานชำระค่าสมัคร</h2><dl class="profile-grid"><div><dt>ยอดเงินที่แจ้งโอน</dt><dd><?= Ui::money($payment->amount) ?> บาท</dd></div><div><dt>วันเวลาโอน</dt><dd><?= Ui::date($payment->transferred_at) ?></dd></div><div><dt>เลขอ้างอิงการโอน</dt><dd><?= Html::encode($payment->transfer_reference ?: '-') ?></dd></div><div><dt>สถานะ</dt><dd><?= Ui::status($payment->status) ?></dd></div></dl><?php if ($payment->review_reason): ?><p class="text-sm mt-4">หมายเหตุ: <?= Html::encode($payment->review_reason) ?></p><?php endif ?><?php if (!Yii::$app->user->identity->isStaff() || Yii::$app->user->identity->canViewDocument('payment_slip')): ?><?= Ui::link('ดาวน์โหลดสลิป', ['document', 'id' => $payment->document_id], 'arrow-down-to-line', 'button-secondary mt-5') ?><?php endif ?></section><?php endif ?>
<?php if (Yii::$app->user->identity->isStaff()): ?><?= $this->render('../operations/_review', compact('application')) ?><?php else: ?><section class="form-section mt-6"><h2>ผลตรวจคุณสมบัติ</h2><?= Ui::status($application->qualification_status) ?><?php if ($application->qualification_reason): ?><p class="mt-4 text-sm"><?= Html::encode($application->qualification_reason) ?></p><?php endif ?><?php if ($application->status === 'correction_required' && $application->profile): ?><div class="mt-5"><?= Ui::link('แก้ไขและส่งใบสมัครใหม่', ['correction', 'id' => $application->id], 'pencil', 'button-primary') ?></div><?php endif ?></section><?php endif ?>
<h2 class="mt-8 mb-5">วิชาสอบและค่าสมัคร</h2><div class="table-scroll"><table class="data-table"><thead><tr><th>รายวิชา</th><th>วันและเวลาสอบ</th><th class="text-right">ค่าสมัครรายวิชา</th></tr></thead><tbody>
<?php foreach ($application->items as $item): ?><tr><td><?= Html::encode($item->subject->name) ?></td><td><?= Ui::date($item->subject->starts_at) ?></td><td class="text-right"><?= $application->program_id ? 'รวมในค่าสมัครโครงการ' : Ui::money($item->fee) ?></td></tr><?php endforeach ?>
</tbody></table></div>
<div class="flex flex-wrap justify-between items-center gap-4 mt-6"><span class="text-muted"><?= $application->program_id ? 'ค่าระเบียบ / ค่าสมัครโครงการ' : 'ค่าสมัครรวม' ?></span><strong>รวม <?= Ui::money($application->total) ?> บาท</strong></div>
<?php if ($snapshot && $snapshot['enrollment_fee'] !== null): ?><p class="text-xs text-muted mt-5">เงินบำรุงวันมอบตัว <?= Ui::money($snapshot['enrollment_fee']) ?> บาท ไม่รวมในยอดค่าสมัคร</p><?php endif ?>
<?php if (Yii::$app->user->identity->can('exam.manage')): ?><?= $this->render('_ticket-settings', ['application' => $application, 'ticketForm' => $ticketForm ?? null]) ?><?php elseif (!Yii::$app->user->identity->isStaff()): ?><div class="heading-actions mt-7"><?= Ui::link('พิมพ์บัตรประจำตัวสอบ', ['tickets'], 'printer') ?><?= Ui::link('ดูผลสอบ', ['results'], 'file-check-2') ?></div><?php endif ?>
