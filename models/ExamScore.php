<?php
namespace app\models;
class ExamScore extends \yii\db\ActiveRecord
{
    public static function tableName() { return 'exam_score'; }
    public function rules() { return [
        ['attendance', 'in', 'range' => ['pending', 'present', 'absent']],
        ['score', 'default', 'value' => null], ['score', 'number', 'min' => 0, 'max' => 10000],
        ['score', 'match', 'pattern' => '/^\d+(?:\.\d{1,2})?$/D'],
        ['score', 'required', 'when' => fn($m) => $m->attendance === 'present'],
        ['score', function ($attribute) { if ($this->attendance !== 'present' && $this->score !== null) $this->addError($attribute, 'กรอกคะแนนได้เฉพาะผู้เข้าสอบ'); }],
    ]; }
}
