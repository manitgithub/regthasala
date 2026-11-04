<?php
use app\helpers\Ui;
use yii\helpers\Html;
$fields = ['title' => ['ชื่อกำหนดการ', 'text'], 'occurs_at' => ['วันและเวลาเริ่ม', 'datetime-local'], 'ends_at' => ['วันและเวลาสิ้นสุด (ถ้ามี)', 'datetime-local'], 'venue' => ['สถานที่ / ช่องทาง', 'text'], 'note' => ['รายละเอียดเพิ่มเติม', 'textarea']];
?>
<fieldset class="schedule-editor-row" data-schedule-row>
<legend class="sr-only">รายการกำหนดการ</legend>
<div class="flex justify-between items-center gap-3 mb-4"><strong class="text-xs text-muted">กำหนดการ <span data-row-number><?= is_numeric($index) ? $index + 1 : '' ?></span></strong><button class="icon-button" type="button" data-remove-schedule title="ลบกำหนดการ" aria-label="ลบกำหนดการ"><?= Ui::icon('trash-2') ?></button></div>
<?= Html::hiddenInput("ScheduleRows[$index][id]", $rowId) ?>
<div class="field-grid">
<?php foreach ($fields as $attribute => [$label, $type]):
    $id = "schedule-$index-$attribute";
    $value = $item->$attribute;
    if ($type === 'datetime-local' && $value) {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);
        if ($date) $value = $date->format('Y-m-d\\TH:i');
    }
    $options = ['class' => 'input', 'id' => $id, 'required' => in_array($attribute, ['title', 'occurs_at'], true)];
    if ($attribute === 'title') $options += ['list' => 'schedule-event-types', 'maxlength' => 190];
    if ($attribute === 'venue') $options['maxlength'] = 190;
    if ($type === 'textarea') $options += ['rows' => 2, 'maxlength' => 2000]; ?>
<div class="field <?= in_array($attribute, ['title', 'note']) ? 'col-span-full' : '' ?>"><?= Html::label($label, $id) ?>
<?= $type === 'textarea' ? Html::textarea("ScheduleRows[$index][$attribute]", $value, $options) : Html::input($type, "ScheduleRows[$index][$attribute]", $value, $options) ?>
<?= Html::error($item, $attribute, ['class' => 'field-error']) ?></div>
<?php endforeach ?>
</div></fieldset>
