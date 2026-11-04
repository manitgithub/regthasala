<?php
use app\helpers\Ui;
use app\models\Application;
use yii\helpers\{Html, Url};
$this->title = $round->name;
$count = Application::find()->where(['round_id' => $round->id])->count();
$isAdmission = $round->registration_mode === 'admission';
$maxFee = $isAdmission ? ($round->programs ? max(array_column($round->programs, 'application_fee')) : 0) : array_sum(array_column($round->subjects, 'fee'));
?>
<?= Ui::link('รอบสอบทั้งหมด', ['index'], 'arrow-left', 'back-link') ?>
<div class="page-heading"><div><div class="flex items-center gap-3 mb-3"><p class="eyebrow mb-0"><?= Html::encode($round->type->name) ?></p><?= Ui::status(Ui::roundState($round)) ?></div><h1><?= Html::encode($round->name) ?></h1><p class="subheading"><?= Html::encode($round->description) ?></p></div><?= Ui::link('แก้ไขรอบสอบ', ['edit', 'entity' => 'round', 'id' => $round->id], 'settings-2') ?></div>
<?php if (Ui::roundState($round) === 'setup'): ?><div class="attention-row mb-7"><?= Ui::icon('circle-alert') ?><div><strong>ยังไม่พร้อมรับสมัคร</strong><p>กรุณากำหนดบัญชีรับเงินก่อน ผู้สมัครจึงจะส่งใบสมัครพร้อมสลิปได้</p></div><?= Ui::link('ตั้งค่าบัญชี', ['edit', 'entity' => 'round', 'id' => $round->id, '#' => 'payment-settings'], 'arrow-right') ?></div><?php endif ?>
<nav class="section-jumps" aria-label="ส่วนของรอบสอบ"><?php if ($isAdmission): ?><a href="#round-programs"><?= Ui::icon('graduation-cap') ?>โครงการ</a><?php endif ?><a href="#round-subjects"><?= Ui::icon('book-open') ?>รายวิชา</a><a href="#round-schedule"><?= Ui::icon('calendar-range') ?>กำหนดการ</a><?= Ui::link('ใบสมัคร ' . $count . ' รายการ', ['applications', 'round' => $round->id], 'files', 'button-quiet') ?></nav>
<div class="metrics"><div><span>เปิดรับสมัคร</span><strong class="metric-date"><?= Ui::date($round->opens_at, false) ?></strong></div><div><span>ปิดรับสมัคร</span><strong class="metric-date"><?= Ui::date($round->closes_at, false) ?></strong></div><div><span>ผู้สมัคร</span><strong><?= $count ?><small>คน</small></strong></div><div><span><?= $isAdmission ? 'ค่าสมัครโครงการสูงสุด' : 'ค่าสมัครครบทุกวิชา' ?></span><strong><?= Ui::money($maxFee) ?><small>บาท</small></strong></div></div>
<div class="round-layout">
<section class="min-w-0">
<?php if ($round->registration_mode === 'admission'): ?>
    <div class="section-bar" id="round-programs"><h2>โครงการรับนักเรียน</h2><?= Ui::link('เพิ่มโครงการ', ['edit', 'entity' => 'program', 'roundId' => $round->id], 'plus', 'button-primary') ?></div>
    <div class="table-scroll mb-8"><table class="data-table"><thead><tr><th>โครงการ</th><th>จำนวนรับ</th><th>เกณฑ์เกรด</th><th>ค่าสมัคร</th><th></th></tr></thead><tbody>
    <?php foreach ($round->programs as $program): ?><tr><td><strong><?= Html::encode($program->code) ?> · <?= $program->gradeLabel ?></strong><small class="block text-muted mt-1"><?= Html::encode($program->name) ?></small></td><td><?= $program->intake ?> คน</td><td><?= $program->minimum_gpa ?><small class="block text-muted"><?= Html::encode($program->gpa_basis) ?></small></td><td><?= Ui::money($program->application_fee) ?></td><td><?= Ui::link('แก้ไข', ['edit', 'entity' => 'program', 'id' => $program->id], 'pencil', 'button-quiet') ?></td></tr><?php endforeach ?>
    <?php if (!$round->programs): ?><tr><td colspan="5" class="empty-state">ยังไม่มีโครงการรับนักเรียน</td></tr><?php endif ?>
    </tbody></table></div>
    <?php if ($round->is_example): ?><div class="mb-8"><?= Ui::link('ดูตัวอย่างแบบฟอร์ม', ['/site/admission-preview', 'id' => $round->id], 'arrow-up-right') ?></div><?php endif ?>
<?php endif ?>
    <div class="section-bar" id="round-subjects"><div><h2>รายวิชาในรอบสอบ</h2><p class="text-sm text-muted mt-1"><?= count($round->subjects) ?> วิชา</p></div><?= Ui::link('เพิ่มวิชา', ['edit', 'entity' => 'subject', 'roundId' => $round->id], 'plus', 'button-primary') ?></div>
    <div class="table-scroll"><table class="data-table"><thead><tr><th>รายวิชา</th><th>วันและเวลาสอบ</th><th>ที่นั่ง</th><th class="text-right">ค่าสมัคร</th><th></th></tr></thead><tbody>
    <?php foreach ($round->subjects as $subject): ?><tr><td><a class="row-title" href="<?= Url::to(['edit', 'entity' => 'subject', 'id' => $subject->id]) ?>"><?= Html::encode($subject->name) ?></a><small class="block text-muted mt-1"><?= Html::encode($subject->code) ?> · <?= $subject->status === 'open' ? 'เปิดรับ' : 'ปิดรับ' ?></small></td><td class="text-sm"><?= Ui::date($subject->starts_at, false) ?><small class="block text-muted mt-1"><?= date('H:i', strtotime($subject->starts_at)) ?>–<?= date('H:i', strtotime($subject->ends_at)) ?></small></td><td><span><?= $subject->getReservedCount() ?></span><span class="text-muted"> / <?= $subject->capacity ?></span></td><td class="text-right tabular-nums font-medium"><?= Ui::money($subject->fee) ?></td><td><a class="icon-button" title="แก้ไขวิชา" aria-label="แก้ไข <?= Html::encode($subject->name) ?>" href="<?= Url::to(['edit', 'entity' => 'subject', 'id' => $subject->id]) ?>"><?= Ui::icon('pencil') ?></a></td></tr><?php endforeach ?>
    <?php if (!$round->subjects): ?><tr><td colspan="5" class="empty-state">ยังไม่มีรายวิชา</td></tr><?php endif ?>
    </tbody></table></div>
    <div class="section-bar mt-10"><h2>เอกสารประกอบการสมัคร</h2><?= Ui::link('แก้ไข', ['edit', 'entity' => 'round', 'id' => $round->id], 'pencil', 'button-quiet') ?></div>
    <div class="document-list"><?php foreach (array_filter(explode("\n", (string) $round->required_documents)) as $line): ?><div><?= Ui::icon('file-text') ?><span><?= Html::encode($line) ?></span><span class="text-xs text-muted ml-auto">เอกสารที่กำหนด</span></div><?php endforeach ?><?php if (!$round->required_documents): ?><p class="text-muted">ยังไม่ได้กำหนดเอกสาร</p><?php endif ?></div>
</section>
<aside class="schedule-section" id="round-schedule"><div class="section-bar"><h2>กำหนดการ</h2><a class="icon-button" href="<?= Url::to(['edit', 'entity' => 'round', 'id' => $round->id]) ?>#schedule-editor" title="เพิ่มกำหนดการ" aria-label="เพิ่มกำหนดการ"><?= Ui::icon('plus') ?></a></div>
    <ol class="timeline">
        <?php $events = [[$round->opens_at, 'เปิดรับสมัคร', null], [$round->closes_at, 'ปิดรับสมัคร', null], [$round->payment_due_at, 'สิ้นสุดชำระเงิน', null]];
        foreach ($round->milestones as $milestone) $events[] = [$milestone->occurs_at, $milestone->title, $milestone];
        usort($events, fn($a, $b) => strcmp($a[0], $b[0]));
        foreach ($events as [$date, $title, $milestone]): ?>
        <li><time><?= Ui::date($date) ?><?= $milestone && $milestone->ends_at ? ' ถึง ' . Ui::date($milestone->ends_at) : '' ?></time><div class="flex items-start justify-between gap-2"><strong><?= Html::encode($title) ?></strong><?php if ($milestone): ?><a class="timeline-edit" href="<?= Url::to(['edit', 'entity' => 'round', 'id' => $round->id]) ?>#schedule-editor" title="แก้ไขกำหนดการ" aria-label="แก้ไข <?= Html::encode($title) ?>"><?= Ui::icon('pencil') ?></a><?php endif ?></div><?php if ($milestone && $milestone->venue): ?><p><?= Html::encode($milestone->venue) ?></p><?php endif ?><?php if ($milestone && $milestone->note): ?><p><?= Html::encode($milestone->note) ?></p><?php endif ?></li>
        <?php endforeach ?>
    </ol>
</aside>
</div>
