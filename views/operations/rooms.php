<?php
use app\helpers\Ui;
use app\models\ExamTicket;
use yii\helpers\Html;
$this->title = 'จัดสอบและห้องสอบ';
?>
<div class="page-heading"><div><p class="eyebrow">4 · จัดสอบ</p><h1><?= $this->title ?></h1></div></div>
<?= $this->render('_round-picker', compact('round', 'rounds')) ?>
<?php if ($round): ?>
<?php
$numberLocked = ExamTicket::find()->where(['round_id' => $round->id])->andWhere(['not', ['exam_number' => null]])->exists();
$numberForm = new \app\models\ExamNumberSettingsForm(['prefix' => \app\services\ExamNumberService::prefix($round), 'digits' => $round->exam_number_digits, 'start' => $round->exam_number_start]);
?>
<section class="form-section mb-8" id="exam-number-settings">
<div class="section-bar"><h2>การรันเลขประจำตัวสอบ</h2><span class="badge badge-neutral"><?= $numberLocked ? 'ล็อกการตั้งค่าแล้ว' : 'ยังไม่ออกเลขสอบ' ?></span></div>
<?= Html::beginForm(['save-number-settings', 'round' => $round->id], 'post') ?>
<div class="field-grid">
<?= Ui::field($numberForm, 'prefix', 'คำนำหน้าเลขสอบ', 'text', ['maxlength' => 30, 'disabled' => $numberLocked]) ?>
<?= Ui::field($numberForm, 'digits', 'จำนวนหลักของเลขลำดับ', 'number', ['min' => 1, 'max' => 9, 'disabled' => $numberLocked]) ?>
<?= Ui::field($numberForm, 'start', 'เลขลำดับเริ่มต้น', 'number', ['min' => 1, 'max' => 999999999, 'disabled' => $numberLocked]) ?>
<div class="field"><span class="text-sm font-medium text-muted">รูปแบบเลขสอบตามการตั้งค่าปัจจุบัน</span><p class="text-xl font-semibold tabular-nums break-all"><?= Html::encode(\app\services\ExamNumberService::prefix($round) . str_pad((string) $round->exam_number_start, (int) $round->exam_number_digits, '0', STR_PAD_LEFT)) ?></p></div>
</div>
<?php if (!$numberLocked): ?><div class="form-footer"><button type="submit" class="button-primary"><?= Ui::icon('check') ?>บันทึกการรันเลข</button></div><?php endif ?>
<?= Html::endForm() ?></section>
<div class="section-bar"><h2>ห้องสอบ <span class="count-label"><?= count($rooms) ?></span></h2><span class="text-sm text-muted">จัดเลขสอบแล้ว <?= (int) $tickets ?> คน</span></div>
<div class="table-scroll"><table class="data-table"><thead><tr><th>สนามสอบ / อาคาร</th><th>ห้อง</th><th>ที่นั่งที่จัด / ความจุ</th><th>รายการ</th></tr></thead><tbody><?php foreach ($rooms as $r): ?><tr><td><?= Html::encode($r->venue) ?></td><td><?= Html::encode($r->name) ?></td><td><?= ExamTicket::find()->where(['room_id' => $r->id])->count() ?> / <?= $r->capacity ?></td><td><?= Ui::link('แก้ไข', ['rooms', 'round' => $round->id, 'edit' => $r->id], 'pencil', 'button-quiet') ?><?= Ui::link('รายชื่อ / ใบเซ็นชื่อ', ['roster', 'id' => $r->id], 'printer') ?></td></tr><?php endforeach ?><?php if (!$rooms): ?><tr><td colspan="4" class="empty-state">ยังไม่มีห้องสอบ</td></tr><?php endif ?></tbody></table></div>
<section class="form-section mt-8"><h2><?= $room->isNewRecord ? 'เพิ่มห้องสอบ' : 'แก้ไขห้องสอบ' ?></h2><?= Html::beginForm(['save-room', 'round' => $round->id], 'post') ?><?= Html::hiddenInput('id', $room->id) ?><?= Html::errorSummary($room, ['class' => 'field-error']) ?><div class="field-grid"><?= Ui::field($room, 'venue', 'สนามสอบ / อาคาร') ?><?= Ui::field($room, 'name', 'ห้องสอบ') ?><?= Ui::field($room, 'capacity', 'จำนวนที่นั่ง', 'number', ['min' => 1, 'max' => 1000]) ?></div><div class="form-footer"><button class="button-primary" type="submit">บันทึกห้องสอบ</button></div><?= Html::endForm() ?></section>
<div class="operations-actions">
<?= Html::beginForm(['allocate', 'round' => $round->id], 'post') ?><button type="submit" class="button-primary"><?= Ui::icon('graduation-cap') ?>จัดที่นั่งผู้มีสิทธิ์สอบที่ยังไม่มีบัตร</button><?= Html::endForm() ?>
<?= Html::beginForm(['publish-tickets', 'round' => $round->id], 'post') ?><?= Html::hiddenInput('publish', '1') ?><button type="submit" class="button-secondary">เปิดพิมพ์บัตรสอบ</button><?= Html::endForm() ?>
<?= Html::beginForm(['publish-tickets', 'round' => $round->id], 'post') ?><?= Html::hiddenInput('publish', '0') ?><button type="submit" class="button-quiet">ปิดพิมพ์บัตรสอบ</button><?= Html::endForm() ?></div>
<?php endif ?>
