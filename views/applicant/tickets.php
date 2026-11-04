<?php
use app\helpers\Ui;
use yii\helpers\Html;
$this->title = 'พิมพ์บัตรประจำตัวสอบ';
?>
<div class="page-heading"><div><p class="eyebrow">ผู้สมัครสอบ</p><h1>พิมพ์บัตรประจำตัวสอบ</h1></div><?= Ui::link('ตรวจสอบสถานะ', ['index'], 'file-check-2') ?></div>
<?php foreach ($applications as $application): $ready = $application->ticket && $application->ticket->published && $application->isEligible(); ?>
<section class="application-record"><div class="section-bar"><div><p class="eyebrow"><?= Html::encode($application->reference) ?></p><h2><?= Html::encode($application->round->name) ?></h2></div><span class="badge <?= $ready ? 'badge-green' : 'badge-neutral' ?>"><?= $ready ? 'เปิดพิมพ์บัตรสอบแล้ว' : 'ยังไม่เปิดพิมพ์บัตรสอบ' ?></span></div>
<?php if ($ready): ?><p class="text-sm mb-5">เลขประจำตัวสอบ <strong><?= Html::encode($application->ticket->exam_number) ?></strong></p><?= Ui::link('ดูและพิมพ์บัตรประจำตัวสอบ', ['ticket', 'id' => $application->id], 'printer', 'button-primary') ?>
<?php else: ?><p class="text-sm text-muted">รอเจ้าหน้าที่ตรวจสอบและเปิดพิมพ์บัตรประจำตัวสอบ</p><?php endif ?></section>
<?php endforeach ?>
<?php if (!$applications): ?><div class="empty-state"><?= Ui::icon('file-text') ?><p>ไม่พบใบสมัคร</p><?= Ui::link('เลือกรอบสอบ', ['/site/index'], 'arrow-right', 'button-quiet') ?></div><?php endif ?>
