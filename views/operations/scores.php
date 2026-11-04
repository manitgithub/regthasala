<?php
use app\helpers\Ui;
use yii\helpers\{Html, ArrayHelper};
$this->title = 'คะแนนและการเข้าสอบ';
$editable = Yii::$app->user->identity->can('results.manage') && !$round?->results_published_at;
?>
<div class="page-heading"><div><p class="eyebrow">5 · ผลสอบ</p><h1><?= $this->title ?></h1></div><?= Ui::link('ผลคัดเลือก / ประกาศผล', ['outcomes', 'round' => $round?->id], 'file-check-2') ?></div>
<?= $this->render('_round-picker', compact('round', 'rounds')) ?>
<?= Html::beginForm(['scores'], 'get', ['class' => 'round-picker']) ?><?= Html::hiddenInput('round', $round?->id) ?><label class="field">รายวิชา<?= Html::dropDownList('subject', $subjectId, ArrayHelper::map($subjects, 'id', 'name'), ['class' => 'input']) ?></label><button type="submit" class="button-secondary">แสดงรายวิชา</button><?= Html::endForm() ?>
<?= Html::beginForm(['save-scores', 'round' => $round?->id], 'post') ?><?= Html::hiddenInput('subject', $subjectId) ?><?= Html::hiddenInput('revision', \app\services\ResultService::revision($round?->id ?? 0)) ?>
<div class="table-scroll"><table class="data-table"><thead><tr><th>เลขสอบ / ผู้สมัคร</th><th>การเข้าสอบ</th><th>คะแนน</th><th>คะแนนเต็ม</th></tr></thead><tbody>
<?php foreach ($provider->getModels() as $item): ?><tr><td><?= Html::encode($item->application->ticket?->exam_number ?? $item->application->reference) ?><small class="cell-secondary"><?= Html::encode($item->application->profile?->data['applicant_name'] ?? $item->application->applicant->name) ?></small></td><td><?= Html::dropDownList('Scores[' . $item->id . '][attendance]', $item->score?->attendance ?? 'pending', ['pending' => 'ยังไม่บันทึก', 'present' => 'เข้าสอบ', 'absent' => 'ขาดสอบ'], ['class' => 'input', 'disabled' => !$editable, 'aria-label' => 'การเข้าสอบ ' . $item->application->reference]) ?></td><td><?= Html::input('number', 'Scores[' . $item->id . '][score]', $item->score?->score, ['class' => 'input score-input', 'min' => 0, 'max' => $item->subject->max_score, 'step' => '0.01', 'disabled' => !$editable, 'aria-label' => 'คะแนน ' . $item->application->reference]) ?></td><td><?= $item->subject->max_score ?></td></tr><?php endforeach ?>
<?php if (!$provider->totalCount): ?><tr><td colspan="4" class="empty-state">ยังไม่มีผู้มีสิทธิ์สอบในรายวิชานี้</td></tr><?php endif ?></tbody></table></div>
<?php if ($editable && $provider->totalCount): ?><div class="form-footer"><button type="submit" class="button-primary">บันทึกคะแนนหน้านี้</button></div><?php endif ?><?= Html::endForm() ?><div class="pagination"><?= \yii\widgets\LinkPager::widget(['pagination' => $provider->pagination]) ?></div>
