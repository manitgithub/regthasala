<?php
namespace app\models;
class Subject extends \yii\db\ActiveRecord
{
    public static function tableName() { return 'subject'; }
    public function beforeSave($insert)
    {
        if ($insert && $this->max_score === null) $this->max_score = 100;
        return parent::beforeSave($insert);
    }
    public function rules() { return [
        [['round_id', 'code', 'name', 'starts_at', 'ends_at', 'capacity', 'fee', 'status'], 'required'],
        [['name', 'code', 'venue'], 'trim'], ['code', 'string', 'max' => 40], [['name', 'venue'], 'string', 'max' => 190],
        ['capacity', 'integer', 'min' => 1, 'max' => 1000000], ['fee', 'number', 'min' => 0, 'max' => 99999999.99],
        ['max_score', 'default', 'value' => 100], ['max_score', 'number', 'min' => 0.01, 'max' => 10000],
        ['max_score', function ($attribute) { if (!$this->isNewRecord && $this->isAttributeChanged($attribute) && (new \yii\db\Query())->from('exam_score s')->innerJoin('application_subject i', 'i.id=s.item_id')->where(['i.subject_id' => $this->id])->exists()) $this->addError($attribute, 'ไม่สามารถเปลี่ยนคะแนนเต็มหลังบันทึกคะแนนสอบ'); }],
        ['status', 'in', 'range' => ['open', 'closed']],
        ['round_id', 'exist', 'targetClass' => ExamRound::class, 'targetAttribute' => 'id'],
        [['starts_at', 'ends_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
        ['ends_at', 'compare', 'compareAttribute' => 'starts_at', 'operator' => '>'],
        ['code', 'unique', 'targetAttribute' => ['round_id', 'code']],
        ['capacity', function ($attribute) { if (!$this->isNewRecord && $this->capacity < $this->getReservedCount()) $this->addError($attribute, 'จำนวนที่นั่งต้องไม่น้อยกว่าจำนวนผู้สมัคร'); }],
    ]; }
    public function attributeLabels() { return ['name' => 'ชื่อวิชา', 'code' => 'รหัสวิชา', 'starts_at' => 'เริ่มสอบ', 'ends_at' => 'สิ้นสุดสอบ', 'capacity' => 'จำนวนที่นั่ง', 'fee' => 'ค่าสมัคร', 'status' => 'สถานะ']; }
    public function getReservedCount(): int { return (int) (new \yii\db\Query())->from('application_subject')->where(['subject_id' => $this->id])->count(); }
}
