<?php
namespace app\models;

class RoundScheduleForm extends \yii\base\Model
{
    public array $items = [];
    public string $revision;
    private array $submittedIds = [];

    public static function forRound(ExamRound $round): self
    {
        $form = new self();
        $form->items = $round->isNewRecord ? [] : $round->getMilestones()->all();
        $form->revision = self::currentRevision($round->isNewRecord ? null : (int) $round->id);
        return $form;
    }
    public static function currentRevision(?int $roundId): string
    {
        $rows = $roundId ? Milestone::find()->where(['round_id' => $roundId])->orderBy('id')->asArray()->all() : [];
        return hash('sha256', json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
    public function loadRows($rows, $revision): void
    {
        $this->items = [];
        $this->submittedIds = [];
        $this->revision = is_string($revision) ? $revision : '';
        if (!is_array($rows) || count($rows) > 50) { $this->addError('items', 'กำหนดการต้องไม่เกิน 50 รายการ'); return; }
        foreach (array_values($rows) as $row) {
            if (!is_array($row)) { $this->addError('items', 'รูปแบบกำหนดการไม่ถูกต้อง'); continue; }
            $item = new Milestone();
            $id = $row['id'] ?? '';
            if ($id !== '' && (!is_scalar($id) || !ctype_digit((string) $id) || (int) $id < 1)) {
                $this->addError('items', 'รหัสกำหนดการไม่ถูกต้อง'); continue;
            }
            $this->submittedIds[] = $id === '' ? null : (int) $id;
            foreach (['title', 'occurs_at', 'ends_at', 'venue', 'note'] as $attribute) {
                $value = $row[$attribute] ?? '';
                if (!is_scalar($value)) { $this->addError('items', 'ข้อมูลกำหนดการไม่ถูกต้อง'); $value = ''; }
                $value = trim((string) $value);
                if (in_array($attribute, ['occurs_at', 'ends_at'], true) && $value !== '') {
                    $date = \DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $value);
                    if ($date && $date->format('Y-m-d\\TH:i') === $value) $value = $date->format('Y-m-d H:i:s');
                }
                $item->$attribute = $value === '' && in_array($attribute, ['ends_at', 'venue', 'note'], true) ? null : $value;
            }
            $this->items[] = $item;
        }
    }
    public function validateForRound(ExamRound $round): bool
    {
        $roundId = $round->isNewRecord ? null : (int) $round->id;
        if (!hash_equals(self::currentRevision($roundId), $this->revision)) $this->addError('items', 'กำหนดการถูกแก้ไขจากอีกหน้าต่าง กรุณาเปิดรอบสอบใหม่ก่อนบันทึก');
        $ownedIds = $roundId ? array_map('intval', Milestone::find()->select('id')->where(['round_id' => $roundId])->column()) : [];
        $ids = array_values(array_filter($this->submittedIds, fn($id) => $id !== null));
        if (count($ids) !== count(array_unique($ids)) || array_diff($ids, $ownedIds)) $this->addError('items', 'กำหนดการที่แก้ไขไม่อยู่ในรอบสอบนี้');
        foreach ($this->items as $index => $item) {
            if (!$item->validate(['title', 'occurs_at', 'ends_at', 'venue', 'note'])) $this->addError('items', 'กำหนดการรายการที่ ' . ($index + 1) . ': ' . implode(' ', $item->getFirstErrors()));
        }
        return !$this->hasErrors();
    }
    public function saveForRound(int $roundId): void
    {
        $keep = [];
        foreach ($this->items as $index => $item) {
            $id = $this->submittedIds[$index] ?? null;
            $target = $id ? Milestone::findOne(['id' => $id, 'round_id' => $roundId]) : new Milestone();
            if (!$target) throw new \RuntimeException('Schedule changed while saving.');
            foreach (['title', 'occurs_at', 'ends_at', 'venue', 'note'] as $attribute) $target->$attribute = $item->$attribute;
            $target->round_id = $roundId;
            $target->save(false);
            $keep[] = $target->id;
        }
        Milestone::deleteAll(['and', ['round_id' => $roundId], ['not in', 'id', $keep]]);
    }
    public function rowId(int $index): string
    {
        if ($this->submittedIds) return (string) ($this->submittedIds[$index] ?? '');
        return (string) ($this->items[$index]->id ?? '');
    }
}
