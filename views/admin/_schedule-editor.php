<?php
use app\helpers\Ui;
use app\models\Milestone;
use yii\helpers\Html;
$suggestions = ['ประกาศรายชื่อผู้มีสิทธิ์สอบ', 'ดาวน์โหลดบัตรสอบ', 'วันสอบ', 'ประกาศผลสอบ', 'รายงานตัว', 'เรียกบัญชีสำรอง', 'มอบตัวและชำระเงินบำรุงการศึกษา', 'ส่งเอกสารเพิ่มเติม'];
?>
<section class="form-section" id="schedule-editor">
<div class="section-bar"><h2>กำหนดการของรอบสอบ</h2><button class="button-secondary" type="button" id="add-schedule"><?= Ui::icon('plus') ?>เพิ่มกำหนดการ</button></div>
<?= Html::hiddenInput('schedule_included', '1') ?><?= Html::hiddenInput('schedule_revision', $schedule->revision) ?>
<?php if ($schedule->hasErrors()): ?><div class="alert alert-error" role="alert"><?= Html::errorSummary($schedule, ['header' => 'ไม่สามารถบันทึกกำหนดการได้']) ?></div><?php endif ?>
<div class="field mb-5"><label for="schedule-preset">เพิ่มจากรายการที่ใช้บ่อย</label><select class="input" id="schedule-preset"><option value="">เลือกกำหนดการ</option><?php foreach ($suggestions as $title): ?><option><?= Html::encode($title) ?></option><?php endforeach ?></select></div>
<datalist id="schedule-event-types"><?php foreach ($suggestions as $title): ?><option value="<?= Html::encode($title) ?>"><?php endforeach ?></datalist>
<div class="schedule-base-dates"><span><?= Ui::icon('calendar-range') ?>วันเปิดรับสมัคร วันปิดรับสมัคร และวันสิ้นสุดชำระเงิน ใช้วันที่ที่กำหนดด้านบน</span></div>
<div id="schedule-rows"><?php foreach ($schedule->items as $index => $item): ?><?= $this->render('_schedule-row', ['item' => $item, 'index' => $index, 'rowId' => $schedule->rowId($index)]) ?><?php endforeach ?></div>
<p class="text-xs text-muted py-5" id="schedule-empty" <?= $schedule->items ? 'hidden' : '' ?>>ยังไม่มีกำหนดการเพิ่มเติม</p>
<p class="text-xs text-muted mt-3" id="schedule-count" role="status"><?= count($schedule->items) ?> / 50 รายการ</p>
<template id="schedule-row-template"><?= $this->render('_schedule-row', ['item' => new Milestone(), 'index' => '__INDEX__', 'rowId' => '']) ?></template>
</section>
