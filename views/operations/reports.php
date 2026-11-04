<?php
use app\helpers\Ui;
use yii\helpers\Html;
$this->title = 'รายงานผลการรับสมัคร';
?>
<div class="page-heading"><div><p class="eyebrow">6 · รายงานผล</p><h1><?= $this->title ?></h1></div><?= Ui::link('ดาวน์โหลด CSV', ['export', 'round' => $round?->id], 'arrow-down-to-line', 'button-primary') ?></div>
<?= $this->render('_round-picker', compact('round', 'rounds')) ?>
<div class="metrics"><div><span>ใบสมัคร</span><strong><?= array_sum(array_column($rows, 'applications')) ?></strong></div><div><span>ผ่านคุณสมบัติ</span><strong><?= array_sum(array_column($rows, 'qualified')) ?></strong></div><div><span>ตรวจรับชำระแล้ว</span><strong><?= array_sum(array_column($rows, 'verified')) ?></strong></div><div><span>รายรับตรวจรับแล้ว</span><strong><?= Ui::money(array_sum(array_column($rows, 'income'))) ?></strong></div></div>
<div class="table-scroll"><table class="data-table"><thead><tr><th>รอบสอบ / โครงการ</th><th>ใบสมัคร</th><th>ผ่านคุณสมบัติ</th><th>ตรวจรับชำระแล้ว</th><th>รอตรวจเงิน</th><th class="text-right">รายรับตรวจรับแล้ว</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= Html::encode($row['round_name']) ?><small class="cell-secondary"><?= Html::encode($row['program'] ?? 'สมัครรายวิชา') ?></small></td><td><?= $row['applications'] ?></td><td><?= $row['qualified'] ?></td><td><?= $row['verified'] ?></td><td><?= $row['pending'] ?></td><td class="text-right"><?= Ui::money($row['income']) ?></td></tr><?php endforeach ?><?php if (!$rows): ?><tr><td colspan="6" class="empty-state">ยังไม่มีข้อมูลรายงาน</td></tr><?php endif ?></tbody></table></div>
<?= Ui::link('รายงานทุกรอบ', ['reports'], 'rotate-ccw', 'button-quiet mt-5') ?>
<h2 class="mt-8 mb-5">ผลคัดเลือกที่บันทึก</h2><div class="type-list"><?php foreach (\app\models\ApplicationResult::OUTCOMES as $key => $label): ?><div><span><?= $label ?></span><strong><?= (int) ($resultCounts[$key]['count'] ?? 0) ?> คน</strong></div><?php endforeach ?></div>
<h2 class="mt-8 mb-5">การเข้าสอบรายวิชา</h2><div class="type-list"><?php foreach (['present' => 'เข้าสอบ', 'absent' => 'ขาดสอบ', 'pending' => 'ยังไม่บันทึกการเข้าสอบ'] as $key => $label): ?><div><span><?= $label ?></span><strong><?= (int) ($attendanceCounts[$key]['count'] ?? 0) ?> รายการวิชา</strong></div><?php endforeach ?></div>
