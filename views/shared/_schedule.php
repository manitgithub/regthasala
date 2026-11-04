<?php
use app\helpers\Ui;
use yii\helpers\Html;
$events = [
    ['title' => 'เปิดรับสมัคร', 'occurs_at' => $round->opens_at, 'ends_at' => null, 'venue' => null, 'note' => null],
    ['title' => 'ปิดรับสมัคร', 'occurs_at' => $round->closes_at, 'ends_at' => null, 'venue' => null, 'note' => null],
    ['title' => 'สิ้นสุดชำระเงิน', 'occurs_at' => $round->payment_due_at, 'ends_at' => null, 'venue' => null, 'note' => null],
];
foreach ($round->milestones as $item) $events[] = $item->attributes;
usort($events, fn($a, $b) => strcmp($a['occurs_at'], $b['occurs_at']));
?>
<section class="<?= Html::encode($sectionClass ?? 'mt-10 pt-6 border-t border-line') ?>" aria-label="กำหนดการของรอบสอบ"><h2 class="mb-5">กำหนดการของรอบสอบ</h2><div class="table-scroll"><table class="data-table"><thead><tr><th>กำหนดการ</th><th>วันและเวลา</th></tr></thead><tbody>
<?php foreach ($events as $item): ?><tr><td><strong class="font-medium"><?= Html::encode($item['title']) ?></strong><?php if ($item['note']): ?><p class="text-xs text-muted mt-2 whitespace-pre-line"><?= Html::encode($item['note']) ?></p><?php endif ?></td><td><?= Ui::date($item['occurs_at']) ?><?php if ($item['ends_at']): ?><span class="block text-muted mt-1">ถึง <?= Ui::date($item['ends_at']) ?></span><?php endif ?></td></tr><?php endforeach ?>
</tbody></table></div></section>
