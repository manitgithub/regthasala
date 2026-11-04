<?php
namespace app\models;
class ExamRoom extends \yii\db\ActiveRecord
{
    public static function tableName() { return 'exam_room'; }
    public function rules() { return [
        [['venue', 'name'], 'trim'], [['venue', 'name', 'capacity'], 'required'],
        ['venue', 'string', 'max' => 190], ['name', 'string', 'max' => 100], ['capacity', 'integer', 'min' => 1, 'max' => 1000],
        ['name', 'unique', 'targetAttribute' => ['round_id', 'venue', 'name']],
        ['capacity', function ($attribute) { if (!$this->isNewRecord && (int) $this->capacity < (int) ExamTicket::find()->where(['room_id' => $this->id])->count()) $this->addError($attribute, 'ความจุต้องไม่น้อยกว่าที่นั่งที่จัดแล้ว'); }],
    ]; }
    public function attributeLabels() { return ['venue' => 'สนามสอบ / อาคาร', 'name' => 'ห้องสอบ', 'capacity' => 'จำนวนที่นั่ง']; }
}
