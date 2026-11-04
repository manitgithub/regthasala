<?php
use app\helpers\Ui;
use yii\helpers\Html;
$this->title = 'ตรวจสอบสถานะ';
?>
<div class="page-heading"><div><p class="eyebrow">ผู้สมัครสอบ</p><h1>ตรวจสอบสถานะ</h1></div><div class="heading-actions"><?= Ui::link('พิมพ์บัตรประจำตัวสอบ', ['tickets'], 'printer') ?><?= Ui::link('เลือกรอบสอบ', ['/site/index'], 'plus', 'button-primary') ?></div></div>
<?php foreach ($applications as $application): $snapshot = $application->admission_snapshot; ?>
<section class="application-record">
    <div class="section-bar"><div><p class="eyebrow"><?= Html::encode($application->reference) ?></p><h2><?= Html::encode($application->round->name) ?></h2><?php if ($snapshot): ?><p class="text-sm text-muted mt-3"><?= Html::encode($snapshot['code'] . ' · ' . $snapshot['name']) ?></p><?php endif ?></div><div class="flex items-center gap-3"><?= Ui::status($application->status) ?><?= Ui::link('ดูใบสมัคร ' . $application->reference, ['view', 'id' => $application->id], 'file-text') ?></div></div>
    <div class="table-scroll"><table class="data-table"><thead><tr><th>รายวิชา</th><th>วันสอบ</th><th class="text-right">ค่าสมัคร</th><th>การชำระเงิน</th></tr></thead><tbody>
    <?php foreach ($application->items as $item): ?><tr><td class="font-medium"><?= Html::encode($item->subject->name) ?></td><td><?= Ui::date($item->subject->starts_at) ?></td><td class="text-right"><?= $snapshot ? 'รวมในค่าสมัครโครงการ' : Ui::money($item->fee) ?></td><td><?= Ui::status($application->status) ?></td></tr><?php endforeach ?>
    </tbody></table></div>
    <div class="flex justify-between items-center gap-4 pt-5"><p class="text-sm text-muted"><?= $snapshot ? 'ค่าสมัครโครงการ · ' : '' ?>สิ้นสุดชำระ <?= Ui::date($application->round->payment_due_at) ?></p><strong>รวม <?= Ui::money($application->total) ?> บาท</strong></div>
</section>
<?php endforeach ?>
<?php if (!$applications): ?><div class="empty-state"><?= Ui::icon('file-text') ?><p class="mt-4">ยังไม่มีใบสมัคร</p></div><?php endif ?>
