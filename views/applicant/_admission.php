<?php
use app\helpers\Ui;
use app\models\AdmissionForm;
use yii\helpers\Html;
?>
<nav class="form-navigation" aria-label="ส่วนของใบสมัคร"><a href="#student-details"><b>01</b>ผู้สมัคร</a><a href="#education-details"><b>02</b>การศึกษา</a><a href="#guardian-details"><b>03</b>ผู้ปกครอง</a><a href="#document-details"><b>04</b>เอกสาร</a><a href="#payment-details"><b>05</b>ชำระเงิน</a></nav>
<section class="form-section" id="student-details"><div class="admission-section-heading"><span>01</span><h2>ข้อมูลผู้สมัครและการติดต่อ</h2></div><div class="field-grid">
<?= Ui::select($admission, 'prefix', 'คำนำหน้า', array_combine(['เด็กชาย', 'เด็กหญิง', 'นาย', 'นางสาว', 'อื่น ๆ'], ['เด็กชาย', 'เด็กหญิง', 'นาย', 'นางสาว', 'อื่น ๆ'])) ?>
<?= Ui::field($admission, 'applicant_name', 'ชื่อและนามสกุลผู้สมัคร', 'text', ['maxlength' => 190, 'autocomplete' => 'name']) ?>
<?php if (Yii::$app->user->isGuest || $preview): ?><?= $this->render('../shared/_identity', ['model' => $identity, 'includeName' => false]) ?><?php endif ?>
<?= Ui::field($admission, 'phone', 'เบอร์โทรศัพท์ผู้สมัคร', 'tel', ['inputmode' => 'tel', 'maxlength' => 10]) ?>
<?= Ui::field($admission, 'email', 'อีเมล (ถ้ามี)', 'email') ?>
<div class="col-span-full"><?= Ui::field($admission, 'address', 'บ้านเลขที่ หมู่ ซอย ถนน', 'textarea', ['rows' => 2, 'maxlength' => 1000]) ?></div>
<?= $this->render('../shared/_thai-address', ['model' => $admission]) ?>
</div></section>
<section class="form-section" id="education-details"><div class="admission-section-heading"><span>02</span><h2>โรงเรียนเดิมและผลการเรียน</h2></div><div class="field-grid">
<?= Ui::field($admission, 'school_name', 'โรงเรียนเดิม / โรงเรียนที่กำลังศึกษา') ?>
<?= Ui::field($admission, 'school_province', 'จังหวัดของโรงเรียน') ?>
<?= Ui::select($admission, 'education_level', 'ระดับการศึกษา', AdmissionForm::LEVELS) ?>
<?= Ui::select($admission, 'education_status', 'สถานะการศึกษา', ['studying' => 'กำลังศึกษา', 'graduated' => 'สำเร็จการศึกษาแล้ว']) ?>
<?= Ui::field($admission, 'graduation_year', 'ปีการศึกษาที่จบ / คาดว่าจะจบ (พ.ศ.)', 'number', ['min' => 2500, 'max' => 2700]) ?>
<div><?= Ui::field($admission, 'gpa', 'เกรดเฉลี่ยตามช่วงชั้นที่กำหนด', 'number', ['min' => 0, 'max' => 4, 'step' => '0.01']) ?><p class="text-xs text-muted mt-2" id="gpa-basis">เลือกโครงการเพื่อดูช่วงผลการเรียนที่ใช้</p></div>
<div><?= Ui::field($admission, 'foreign_gpa', 'เกรดเฉลี่ยกลุ่มสาระภาษาต่างประเทศ', 'number', ['min' => 0, 'max' => 4, 'step' => '0.01']) ?><p class="text-xs text-muted mt-2" id="foreign-gpa-rule">กรอกเมื่อโครงการกำหนด</p></div>
</div></section>
<section class="form-section" id="guardian-details"><div class="admission-section-heading"><span>03</span><h2>ข้อมูลผู้ปกครอง</h2></div><div class="field-grid">
<?= Ui::field($admission, 'guardian_name', 'ชื่อ–นามสกุลผู้ปกครอง') ?>
<?= Ui::select($admission, 'guardian_relationship', 'ความสัมพันธ์กับผู้สมัคร', ['บิดา' => 'บิดา', 'มารดา' => 'มารดา', 'ญาติ' => 'ญาติ', 'ผู้ปกครองตามกฎหมาย' => 'ผู้ปกครองตามกฎหมาย', 'อื่น ๆ' => 'อื่น ๆ']) ?>
<?= Ui::field($admission, 'guardian_phone', 'เบอร์โทรศัพท์ผู้ปกครอง', 'tel', ['maxlength' => 10]) ?>
</div></section>
<section class="form-section" id="document-details"><div class="admission-section-heading"><span>04</span><h2>รูปถ่ายสำหรับสมัครสอบ</h2></div><p class="text-xs text-muted mb-5">ตอนสมัครแนบเฉพาะรูปถ่ายนักเรียน JPG / PNG ขนาดไม่เกิน 5 MB ยังไม่ต้องส่งสำเนาบัตรประชาชนหรือเอกสารอื่น</p>
<div class="upload-list">
<?php foreach (AdmissionForm::APPLICATION_DOCUMENTS as $kind => $label): ?><div class="upload-row"><div class="flex gap-3 items-center"><?= Ui::icon('file-text') ?><label for="<?= Html::getInputId($admission, $kind) ?>"><?= Html::encode($label) ?></label></div><div><?= Html::activeFileInput($admission, $kind, ['class' => 'upload-input', 'id' => Html::getInputId($admission, $kind), 'required' => true, 'accept' => '.jpg,.jpeg,.png']) ?><?= Html::error($admission, $kind, ['class' => 'field-error']) ?></div></div><?php endforeach ?>
</div><h3 class="font-semibold mt-6 mb-3">เอกสารนำส่งหลังผ่านการคัดเลือก</h3><ul class="text-sm text-muted list-disc pl-5 leading-7"><?php foreach (AdmissionForm::ENROLLMENT_DOCUMENTS as $label): ?><li><?= Html::encode($label) ?></li><?php endforeach ?></ul><p class="text-xs text-muted mt-4">นำเอกสารมายื่นในวันรายงานตัวหรือมอบตัวตามประกาศของโรงเรียน ผลการเรียนและคุณสมบัติที่กรอกจะตรวจสอบกับหลักฐานภายหลัง</p></section>
