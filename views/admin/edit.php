<?php
use app\helpers\Ui;
use yii\helpers\{Html, ArrayHelper};
$labels = ['round' => 'รอบสอบ', 'type' => 'ประเภทการสอบ', 'subject' => 'รายวิชา', 'milestone' => 'กำหนดการ', 'program' => 'โครงการรับนักเรียน'];
$this->title = ($model->isNewRecord ? 'สร้าง' : 'แก้ไข') . $labels[$entity];
$back = $round ? ['round', 'id' => $round->id] : ($entity === 'round' && !$model->isNewRecord ? ['round', 'id' => $model->id] : ['index']);
?>
<?= Ui::link('กลับ', $back, 'arrow-left', 'back-link') ?>
<div class="page-heading"><div><p class="eyebrow"><?= Html::encode($round ? $round->name : 'ตั้งค่าการสอบ') ?></p><h1><?= Html::encode($this->title) ?></h1></div></div>
<?= Html::beginForm('', 'post', ['class' => 'editor-form', 'id' => 'editor-form']) ?>
<?php if ($model->hasErrors()): ?><div class="alert alert-error" role="alert"><?= Html::errorSummary($model, ['header' => 'กรุณาตรวจสอบข้อมูล']) ?></div><?php endif ?>
<div class="form-section"><h2>ข้อมูล<?= $labels[$entity] ?></h2><div class="field-grid">
<?php if ($entity === 'round'): ?>
    <div class="field"><label for="exam-type">ประเภทการสอบ</label><?= Html::activeDropDownList($model, 'exam_type_id', ArrayHelper::map($types, 'id', 'name'), ['id' => 'exam-type', 'class' => 'input', 'prompt' => 'เลือกประเภทการสอบ', 'required' => true]) ?></div>
    <?= Ui::field($model, 'name', 'ชื่อรอบสอบ') ?>
    <?= Ui::select($model, 'registration_mode', 'รูปแบบรับสมัคร', ['subjects' => 'สมัครรายวิชา คิดค่าธรรมเนียมแยกวิชา', 'admission' => 'รับนักเรียน เลือกโครงการและสอบตามชุดวิชา']) ?>
    <div class="col-span-full"><?= Ui::field($model, 'description', 'รายละเอียด', 'textarea', ['rows' => 3]) ?></div>
    <?= Ui::field($model, 'opens_at', 'เปิดรับสมัคร', 'datetime-local') ?>
    <?= Ui::field($model, 'closes_at', 'ปิดรับสมัคร', 'datetime-local') ?>
    <?= Ui::field($model, 'payment_due_at', 'สิ้นสุดชำระเงิน', 'datetime-local') ?>
    <div class="field"><label for="round-status">สถานะ</label><?= Html::activeDropDownList($model, 'status', ['draft' => 'แบบร่าง', 'published' => 'เผยแพร่ / เปิดตามช่วงเวลารับสมัคร', 'closed' => 'ปิดรอบสอบ'], ['id' => 'round-status', 'class' => 'input']) ?></div>
    <div class="col-span-full"><?= Ui::field($model, 'required_documents', 'เอกสารที่ต้องใช้ (1 รายการต่อบรรทัด)', 'textarea', ['rows' => 4]) ?></div>
<?php elseif ($entity === 'subject'): ?>
    <?= Ui::field($model, 'code', 'รหัสวิชา') ?><?= Ui::field($model, 'name', 'ชื่อรายวิชา') ?>
    <?= Ui::field($model, 'starts_at', 'เริ่มสอบ', 'datetime-local') ?><?= Ui::field($model, 'ends_at', 'สิ้นสุดสอบ', 'datetime-local') ?>
    <?= Ui::field($model, 'capacity', 'จำนวนที่นั่ง', 'number', ['min' => 1, 'max' => 1000000]) ?>
    <?= Ui::field($model, 'fee', 'ค่าสมัคร (บาท)', 'number', ['min' => 0, 'max' => 99999999.99, 'step' => '0.01']) ?>
    <?= Ui::field($model, 'max_score', 'คะแนนเต็ม', 'number', ['min' => '0.01', 'max' => 10000, 'step' => '0.01']) ?>
    <?= Ui::field($model, 'venue', 'สนามสอบ / อาคาร / ห้อง') ?>
    <div class="field"><label for="subject-status">สถานะรับสมัคร</label><?= Html::activeDropDownList($model, 'status', ['open' => 'เปิดรับสมัคร', 'closed' => 'ปิดรับสมัคร'], ['id' => 'subject-status', 'class' => 'input']) ?></div>
<?php elseif ($entity === 'program'): ?>
    <?= Ui::field($model, 'code', 'รหัสโครงการ เช่น EP / SMTP') ?><?= Ui::field($model, 'name', 'ชื่อโครงการ') ?>
    <?= Ui::select($model, 'target_grade', 'ระดับชั้นที่รับ', ['m1' => 'มัธยมศึกษาปีที่ 1', 'm4' => 'มัธยมศึกษาปีที่ 4']) ?>
    <?= Ui::field($model, 'academic_year', 'ปีการศึกษา (พ.ศ.)', 'number', ['min' => 2500, 'max' => 2700]) ?>
    <?= Ui::field($model, 'intake', 'จำนวนรับเข้าศึกษา', 'number', ['min' => 1]) ?>
    <?= Ui::field($model, 'gpa_basis', 'ช่วงผลการเรียน เช่น ป.4–ป.5 / ม.ต้น 5 ภาคเรียน') ?>
    <?= Ui::field($model, 'minimum_gpa', 'เกรดเฉลี่ยขั้นต่ำ', 'number', ['min' => 0, 'max' => 4, 'step' => '0.01']) ?>
    <?= Ui::field($model, 'minimum_foreign_gpa', 'เกรดภาษาต่างประเทศขั้นต่ำ (ถ้ากำหนด)', 'number', ['min' => 0, 'max' => 4, 'step' => '0.01']) ?>
    <?= Ui::field($model, 'application_fee', 'ค่าระเบียบ / ค่าสมัครโครงการ (บาท)', 'number', ['min' => 0, 'step' => '0.01']) ?>
    <?= Ui::field($model, 'enrollment_fee', 'เงินบำรุงวันมอบตัว (แสดงข้อมูลเท่านั้น)', 'number', ['min' => 0, 'step' => '0.01']) ?>
    <?= Ui::select($model, 'status', 'สถานะโครงการ', ['open' => 'เปิดรับสมัคร', 'closed' => 'ปิดรับสมัคร']) ?>
    <fieldset class="col-span-full"><legend class="text-sm font-medium mb-4">ชุดวิชาสอบของโครงการ</legend><div class="grid gap-3">
        <?php $selected = Yii::$app->request->isPost ? (array) Yii::$app->request->post('program_subjects', []) : array_column($model->subjects, 'id'); ?>
        <?php foreach ($round->subjects as $subject): ?><label class="flex gap-3 items-center"><?= Html::checkbox('program_subjects[]', in_array($subject->id, $selected), ['value' => $subject->id]) ?><?= Html::encode($subject->name) ?> <small class="text-muted"><?= Ui::date($subject->starts_at) ?></small></label><?php endforeach ?>
        <?php if (!$round->subjects): ?><p class="text-muted">เพิ่มรายวิชาในรอบสอบก่อนสร้างโครงการ</p><?php endif ?>
    </div></fieldset>
<?php elseif ($entity === 'milestone'): ?>
    <?= Ui::field($model, 'title', 'ชื่อกำหนดการ') ?><?= Ui::field($model, 'occurs_at', 'วันและเวลา', 'datetime-local') ?>
    <?= Ui::field($model, 'ends_at', 'วันและเวลาสิ้นสุด (ถ้ามี)', 'datetime-local') ?><?= Ui::field($model, 'venue', 'สถานที่ / ช่องทาง') ?>
    <div class="col-span-full"><?= Ui::field($model, 'note', 'รายละเอียดเพิ่มเติม', 'textarea', ['rows' => 3]) ?></div>
<?php else: ?><?= Ui::field($model, 'name', 'ชื่อประเภทการสอบ') ?><?php endif ?>
</div></div>
<?php if ($entity === 'round'): ?><section class="form-section" id="payment-settings"><h2>บัญชีรับชำระค่าสมัครของรอบสอบนี้</h2><div class="field-grid">
<?= Ui::field($model, 'payment_bank', 'ธนาคาร') ?>
<?= Ui::field($model, 'payment_account', 'เลขบัญชีรับเงิน', 'text', ['maxlength' => 190]) ?>
<?= Ui::field($model, 'payment_account_name', 'ชื่อบัญชีรับเงิน') ?>
<div class="col-span-full"><?= Ui::field($model, 'payment_instructions', 'รายละเอียด / เงื่อนไขการชำระเงิน', 'textarea', ['rows' => 3, 'maxlength' => 5000]) ?></div>
</div></section><?php endif ?>
<?php if ($schedule): ?><?= $this->render('_schedule-editor', compact('schedule')) ?><?php endif ?>
<div class="form-footer"><?= Ui::link('ยกเลิก', $back) ?><button type="submit" class="button-primary"><?= Ui::icon('check') ?>บันทึก<?= $labels[$entity] ?></button></div>
<?= Html::endForm() ?>
