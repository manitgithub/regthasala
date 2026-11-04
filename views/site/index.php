<?php
use app\helpers\Ui;
use yii\helpers\Html;
$this->title = 'รอบสอบที่เปิดรับ';
?>
<section class="school-heading" aria-label="โรงเรียนท่าศาลาประสิทธิ์ศึกษา"><img class="school-crest" src="/images/thasala-crest.png" alt="ตราโรงเรียน" width="64" height="80"><div><p class="school-heading-name"><?= Html::encode(Yii::$app->params['schoolName']) ?></p><p class="school-heading-system"><?= Html::encode(Yii::$app->params['systemName']) ?></p></div><div class="school-year">ปีการศึกษา<strong><?= (int) date('Y') + 543 ?></strong></div></section>
<?php if ($rounds): ?>
<section class="admission-banner" aria-labelledby="admission-banner-title">
    <div class="admission-banner-title"><?= Ui::icon('calendar-range') ?><h2 id="admission-banner-title">กำหนดการรับสมัครออนไลน์</h2></div>
    <dl class="admission-banner-dates">
    <?php foreach ($rounds as $round): ?>
        <div><dt><?= Html::encode($round->name) ?></dt><dd><span>ตั้งแต่ <time datetime="<?= Html::encode(str_replace(' ', 'T', $round->opens_at)) ?>"><?= Ui::date($round->opens_at) ?> น.</time></span><span>ถึง <time datetime="<?= Html::encode(str_replace(' ', 'T', $round->closes_at)) ?>"><?= Ui::date($round->closes_at) ?> น.</time></span></dd></div>
    <?php endforeach ?>
    </dl>
</section>
<?php endif ?>
<div class="page-heading"><div><p class="eyebrow">รับสมัครออนไลน์</p><h1>รอบสอบที่เปิดรับ</h1></div><?php if (Yii::$app->user->isGuest): ?><div class="heading-actions"><?= Ui::link('ตรวจสอบสถานะ', ['status'], 'file-check-2') ?><?= Ui::link('พิมพ์บัตรสอบ', ['exam-card'], 'printer') ?></div><?php endif ?></div>
<div class="public-rounds">
<?php foreach ($rounds as $round):
    $programs = array_values(array_filter($round->programs, static fn ($program) => $program->status === 'open'));
    $grades = array_unique(array_map(static fn ($program) => $program->getGradeLabel(), $programs));
?>
<article class="public-round">
    <header class="round-card-header">
        <div class="round-grade"><?= $grades ? Html::encode(implode(' / ', $grades)) : Ui::icon('graduation-cap') ?></div>
        <div class="round-card-type"><span><?= Html::encode($round->type->name) ?></span><strong><?= $round->registration_mode === 'admission' ? 'รับสมัครนักเรียน' : 'สมัครสอบรายวิชา' ?></strong></div>
        <?= $round->isOpen() ? Ui::status('open') : '<span class="badge badge-neutral">นอกช่วงรับสมัคร</span>' ?>
    </header>
    <div class="round-card-body">
        <h2><?= Html::encode($round->name) ?></h2>
        <dl class="round-facts">
            <div><dt><?= Ui::icon('calendar-range') ?>เริ่มรับสมัคร</dt><dd><time datetime="<?= Html::encode(str_replace(' ', 'T', $round->opens_at)) ?>"><?= Ui::date($round->opens_at) ?> น.</time></dd></div>
            <div><dt><?= Ui::icon('calendar-range') ?>รับสมัครถึง</dt><dd><time datetime="<?= Html::encode(str_replace(' ', 'T', $round->closes_at)) ?>"><?= Ui::date($round->closes_at) ?> น.</time></dd></div>
        </dl>
        <?php if ($programs): ?>
        <div class="round-program-heading"><h3>โครงการที่เปิดรับ</h3><span><?= count($programs) ?> โครงการ</span></div>
        <ul class="round-program-list">
            <?php foreach ($programs as $program): ?>
            <li><div class="round-program-name"><strong><?= Html::encode($program->code) ?></strong><span><?= Html::encode($program->name) ?></span></div><div class="round-program-meta"><span>รับ <b><?= (int) $program->intake ?></b> คน</span><span>ค่าสมัคร <b><?= Ui::money($program->application_fee) ?></b> บาท</span></div></li>
            <?php endforeach ?>
        </ul>
        <?php else: ?>
        <p class="round-description"><?= Html::encode($round->description) ?></p>
        <p class="round-subject-count"><?= Ui::icon('book-open') ?><?= count($round->subjects) ?> รายวิชา</p>
        <?php endif ?>
    </div>
    <footer class="round-card-footer"><?= Ui::link('สมัครสอบ', ['/applicant/apply', 'id' => $round->id], 'arrow-right', 'button-primary') ?></footer>
</article>
<?php endforeach ?>
<?php if (!$rounds): ?><div class="empty-state">ยังไม่มีรอบสอบที่เผยแพร่</div><?php endif ?>
</div>
