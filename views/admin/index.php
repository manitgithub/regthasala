<?php
use app\helpers\Ui;
use yii\helpers\{Html, Url};
$this->title = 'การสอบและรอบสอบ';
$states = ['all' => count($rounds), 'open' => 0, 'setup' => 0, 'draft' => 0, 'closed' => 0];
$attention = [];
foreach ($rounds as $round) {
    $state = Ui::roundState($round);
    $filter = in_array($state, ['closed', 'ended']) ? 'closed' : ($state === 'scheduled' ? 'open' : $state);
    $states[$filter] = ($states[$filter] ?? 0) + 1;
    if ($state === 'setup') $attention[] = $round;
}
?>
<div class="page-heading dashboard-heading"><div><p class="eyebrow">งานทะเบียนและจัดสอบ <span class="heading-divider">/</span> ปีการศึกษา <?= (int) date('Y') + 543 ?></p><h1>การสอบและรอบสอบ</h1></div><div class="heading-actions"><?= Ui::link('หน้ารับสมัคร', ['/site/index'], 'arrow-up-right') ?><?= Ui::link('สร้างรอบสอบ', ['edit', 'entity' => 'round'], 'plus', 'button-primary') ?></div></div>
<dl class="overview-stats">
    <div><dt><?= Ui::icon('calendar-range') ?>รอบสอบทั้งหมด</dt><dd><?= count($rounds) ?><small>รอบ</small></dd><span><?= $states['open'] ?> รอบพร้อมรับสมัคร / รอเปิด</span></div>
    <div class="stat-review"><dt><?= Ui::icon('file-check-2') ?>รอตรวจสอบชำระเงิน</dt><dd><?= (int) $reviewCount ?><small>รายการ</small></dd><?php if (Yii::$app->user->identity->can('applications.view')): ?><a class="stat-link" href="<?= Url::to(['applications', 'status' => 'payment_review']) ?>">ตรวจใบสมัคร <?= Ui::icon('arrow-right') ?></a><?php else: ?><span>รอเจ้าหน้าที่การเงินพิจารณา</span><?php endif ?></div>
    <div><dt><?= Ui::icon('files') ?>ใบสมัครทั้งหมด</dt><dd><?= (int) $applicationCount ?><small>รายการ</small></dd><span>รวมทุกรอบสอบ</span></div>
    <div><dt><?= Ui::icon('book-open') ?>รายวิชาทั้งหมด</dt><dd><?= (int) $subjectCount ?><small>วิชา</small></dd><span><?= count($types) ?> ประเภทการสอบ</span></div>
</dl>
<?php if ($attention): ?>
<section class="attention-section" aria-labelledby="attention-title"><div class="section-bar"><h2 id="attention-title"><?= Ui::icon('circle-alert') ?>ต้องดำเนินการ <span class="count-label"><?= count($attention) ?></span></h2></div>
<?php foreach ($attention as $round): ?><div class="attention-row"><span class="attention-dot" aria-hidden="true"></span><div><strong><?= Html::encode($round->name) ?></strong><p>ยังไม่ได้ตั้งค่าบัญชีรับค่าสมัคร ผู้สมัครยังยืนยันใบสมัครไม่ได้</p></div><?= Ui::link('ตั้งค่าบัญชี', ['edit', 'entity' => 'round', 'id' => $round->id, '#' => 'payment-settings'], 'arrow-right', 'button-secondary') ?></div><?php endforeach ?>
</section><?php endif ?>
<section aria-labelledby="rounds-title"><div class="section-bar"><h2 id="rounds-title">รอบสอบทั้งหมด <span class="count-label"><?= count($rounds) ?></span></h2><label class="search-field"><?= Ui::icon('search') ?><input type="search" data-filter="round-list" aria-label="ค้นหารอบสอบ" placeholder="ค้นหาชื่อรอบ / ประเภท..."></label></div>
<div class="filter-segments" role="group" aria-label="กรองสถานะรอบสอบ" data-filter-tabs="round-list">
<?php foreach (['all' => 'ทั้งหมด', 'open' => 'พร้อมรับสมัคร / รอเปิด', 'setup' => 'ต้องตั้งค่า', 'draft' => 'แบบร่าง', 'closed' => 'ปิดแล้ว'] as $value => $label): ?><button type="button" data-round-state="<?= $value ?>" aria-pressed="<?= $value === 'all' ? 'true' : 'false' ?>"><?= $label ?><span><?= $states[$value] ?></span></button><?php endforeach ?>
</div>
<div class="table-scroll responsive-table-wrap"><table class="data-table rounds-table responsive-table" id="round-list"><thead><tr><th>รอบสอบ / ประเภท</th><th>ช่วงรับสมัคร</th><th>ผู้สมัคร / วิชา</th><th>สถานะ</th><th><span class="sr-only">จัดการ</span></th></tr></thead><tbody>
<?php foreach ($rounds as $round): $state = Ui::roundState($round); $filter = in_array($state, ['closed', 'ended']) ? 'closed' : ($state === 'scheduled' ? 'open' : $state); ?>
<tr data-search-row data-state="<?= $filter ?>">
    <td class="round-name-cell"><div class="round-name"><span class="round-symbol <?= $state === 'setup' ? 'needs-setup' : '' ?>"><?= Ui::icon($round->registration_mode === 'admission' ? 'graduation-cap' : 'book-open') ?></span><div><a class="row-title" href="<?= Url::to(['round', 'id' => $round->id]) ?>"><?= Html::encode($round->name) ?></a><small><?= Html::encode($round->type->name) ?></small></div></div></td>
    <td data-label="รับสมัคร"><span class="date-range"><?= Ui::date($round->opens_at, false) ?><span>ถึง <?= Ui::date($round->closes_at, false) ?></span></span></td>
    <td data-label="ผู้สมัคร / วิชา"><strong class="tabular-nums"><?= (int) ($roundApplicationCounts[$round->id]['count'] ?? 0) ?></strong> <span class="text-muted">คน</span><small class="cell-secondary"><?= count($round->subjects) ?> วิชา<?= $round->registration_mode === 'admission' ? ' · ' . count($round->programs) . ' โครงการ' : '' ?></small></td>
    <td data-label="สถานะ"><?= Ui::status($state) ?></td>
    <td class="row-action"><?= Ui::link('จัดการ', ['round', 'id' => $round->id], 'arrow-right', 'button-secondary') ?></td>
</tr><?php endforeach ?>
<tr data-filter-empty <?= $rounds ? 'hidden' : '' ?>><td colspan="5" class="empty-state"><?= Ui::icon('search') ?><p>ไม่พบรอบสอบ</p><small>ลองเปลี่ยนคำค้นหรือสถานะที่เลือก</small></td></tr>
</tbody></table></div><p class="result-count" data-filter-count="round-list" aria-live="polite">แสดง <?= count($rounds) ?> จาก <?= count($rounds) ?> รอบ</p></section>
<section class="type-section" aria-labelledby="types-title"><div class="section-bar"><h2 id="types-title">ประเภทการสอบ <span class="count-label"><?= count($types) ?></span></h2><?= Ui::link('เพิ่มประเภท', ['edit', 'entity' => 'type'], 'plus', 'button-quiet') ?></div>
<div class="type-list"><?php foreach ($types as $type): ?><div><?= Ui::icon('bookmark') ?><span><?= Html::encode($type->name) ?></span><?= Ui::link('แก้ไข', ['edit', 'entity' => 'type', 'id' => $type->id], 'pencil', 'button-quiet') ?></div><?php endforeach ?></div></section>
