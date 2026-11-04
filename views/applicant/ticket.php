<?php
use app\helpers\Ui;
use yii\helpers\{Html, Url};
$ticket = $application->ticket;
$profile = $application->profile?->data ?? [];
$snapshot = $application->admission_snapshot;
$photo = $application->getCurrentDocuments()->andWhere(['kind' => 'photo'])->exists();
$this->title = 'บัตรประจำตัวสอบ ' . $ticket->exam_number;
?>
<div class="ticket-actions section-bar"><?= Ui::link('รายการบัตรสอบ', ['tickets'], 'arrow-left', 'back-link mb-0') ?><button type="button" class="button-primary" data-print-ticket><?= Ui::icon('printer') ?>พิมพ์บัตร / บันทึก PDF</button></div>
<article class="ticket-sheet" aria-label="บัตรประจำตัวสอบ">
<header class="ticket-header"><img src="/images/thasala-crest.png" alt="ตราโรงเรียน" width="64" height="72"><div><p><?= Html::encode(Yii::$app->params['schoolName']) ?></p><h1>บัตรประจำตัวสอบ</h1><p><?= Html::encode($application->round->name) ?></p></div></header>
<div class="ticket-identity"><div><p class="text-sm text-muted mb-2">เลขประจำตัวสอบ</p><strong class="ticket-number"><?= Html::encode($ticket->exam_number) ?></strong><h2 class="mt-4"><?= Html::encode(($profile['prefix'] ?? '') . ' ' . ($profile['applicant_name'] ?? $application->applicant->name)) ?></h2><?php if ($snapshot): ?><p class="text-sm mt-2"><?= Html::encode($snapshot['code'] . ' · ' . $snapshot['name']) ?></p><?php endif ?><p class="text-xs text-muted mt-3">เลขที่ใบสมัคร <?= Html::encode($application->reference) ?></p></div>
<?php if ($photo): ?><img class="ticket-photo" src="<?= Url::to(['ticket-photo', 'id' => $application->id]) ?>" alt="รูปถ่ายผู้สมัคร"><?php else: ?><div class="ticket-photo ticket-photo-placeholder">รูปถ่ายผู้สมัคร</div><?php endif ?></div>
<dl class="ticket-location"><div><dt>สนามสอบ / อาคาร</dt><dd><?= Html::encode($ticket->venue) ?></dd></div><div><dt>ห้องสอบ</dt><dd><?= Html::encode($ticket->room) ?></dd></div><div><dt>เลขที่นั่งสอบ</dt><dd><?= Html::encode($ticket->seat) ?></dd></div></dl>
<h2 class="mt-7 mb-4">ตารางสอบ</h2><table class="ticket-timetable"><thead><tr><th scope="col">รายวิชา</th><th scope="col">วันที่สอบ</th><th scope="col">เวลา</th></tr></thead><tbody>
<?php foreach ($application->items as $item): ?><tr><td><?= Html::encode($item->subject->name) ?></td><td><?= Ui::date($item->subject->starts_at, false) ?></td><td><?= date('H:i', strtotime($item->subject->starts_at)) ?> - <?= date('H:i', strtotime($item->subject->ends_at)) ?> น.</td></tr><?php endforeach ?>
</tbody></table>
<?php if ($ticket->note): ?><section class="ticket-note"><h2>ข้อปฏิบัติ / หมายเหตุ</h2><p><?= Html::encode($ticket->note) ?></p></section><?php endif ?>
</article>
