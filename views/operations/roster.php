<?php
use app\helpers\Ui;
use yii\helpers\Html;
$this->title = 'ใบเซ็นชื่อผู้เข้าสอบ';
?>
<div class="ticket-actions section-bar"><?= Ui::link('กลับห้องสอบ', ['rooms', 'round' => $round->id], 'arrow-left') ?><button type="button" class="button-primary" data-print-ticket><?= Ui::icon('printer') ?>พิมพ์ใบเซ็นชื่อ</button></div>
<section class="ticket-sheet"><h1>ใบเซ็นชื่อผู้เข้าสอบ</h1><p class="mt-4"><?= Html::encode(Yii::$app->params['schoolName']) ?></p><p class="mt-2"><?= Html::encode($round->name) ?></p><p class="my-5"><?= Html::encode($room->venue . ' · ห้อง ' . $room->name) ?></p><table class="ticket-timetable"><thead><tr><th>ที่นั่ง</th><th>เลขประจำตัวสอบ</th><th>ชื่อ–สกุล</th><th>ลายมือชื่อ</th></tr></thead><tbody><?php foreach ($tickets as $ticket): $app = \app\models\Application::findOne($ticket->application_id); ?><tr><td><?= Html::encode($ticket->seat) ?></td><td><?= Html::encode($ticket->exam_number) ?></td><td><?= Html::encode($app->profile?->data['applicant_name'] ?? $app->applicant->name) ?></td><td style="min-width: 100px; height: 50px;"></td></tr><?php endforeach ?></tbody></table></section>
