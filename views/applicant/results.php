<?php
use app\helpers\Ui;
use app\models\ApplicationResult;
use yii\helpers\Html;
$this->title = 'ผลสอบ';
?>
<div class="page-heading"><div><p class="eyebrow">ผู้สมัครสอบ</p><h1>ผลสอบ</h1></div><?= Ui::link('ตรวจสอบสถานะ', ['index'], 'file-check-2') ?></div>
<?php foreach ($applications as $application): ?><section class="application-record"><div class="section-bar"><h2><?= Html::encode($application->round->name) ?></h2><span class="eyebrow"><?= Html::encode($application->reference) ?></span></div>
<?php if ($application->round->results_published_at && $application->result && $application->isEligible()): ?><p class="text-lg font-semibold mb-5"><?= Html::encode(ApplicationResult::OUTCOMES[$application->result->outcome]) ?></p><div class="table-scroll"><table class="data-table"><thead><tr><th>รายวิชา</th><th>การเข้าสอบ</th><th>คะแนน</th><th>คะแนนเต็ม</th></tr></thead><tbody><?php foreach ($application->items as $item): ?><tr><td><?= Html::encode($item->subject->name) ?></td><td><?= $item->score?->attendance === 'present' ? 'เข้าสอบ' : 'ขาดสอบ' ?></td><td><?= $item->score?->score ?? '-' ?></td><td><?= $item->subject->max_score ?></td></tr><?php endforeach ?></tbody></table></div><?php if ($application->result->note): ?><p class="mt-4 text-sm"><?= Html::encode($application->result->note) ?></p><?php endif ?>
<?php else: ?><p class="text-sm text-muted">ยังไม่เปิดประกาศผลสอบ</p><?php endif ?></section><?php endforeach ?>
<?php if (!$applications): ?><div class="empty-state">ไม่พบใบสมัคร</div><?php endif ?>
