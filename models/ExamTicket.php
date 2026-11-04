<?php
namespace app\models;
class ExamTicket extends \yii\db\ActiveRecord
{
    public static function tableName() { return 'exam_ticket'; }
    public function beforeValidate()
    {
        if (!$this->room_id && $this->round_id && $this->venue && $this->room) $this->room_id = ExamRoom::find()->select('id')->where(['round_id' => $this->round_id, 'venue' => $this->venue, 'name' => $this->room])->scalar() ?: null;
        return parent::beforeValidate();
    }
    public function rules() { return [
        [['exam_number', 'venue', 'room', 'seat', 'note'], 'trim'],
        [['exam_number', 'room', 'seat', 'venue', 'note'], 'default', 'value' => null],
        [['exam_number', 'venue', 'room', 'seat'], 'required', 'when' => fn($model) => (bool) $model->published],
        [['exam_number', 'seat'], 'string', 'max' => 50], ['room', 'string', 'max' => 100],
        ['venue', 'string', 'max' => 190], ['note', 'string', 'max' => 2000], ['published', 'boolean'],
        ['exam_number', 'unique', 'targetAttribute' => ['round_id', 'exam_number'], 'message' => 'เลขประจำตัวสอบซ้ำในรอบนี้'],
        ['seat', function ($attribute) { if ($this->room_id) { $room = ExamRoom::findOne($this->room_id); if (!$room || (int) $room->round_id !== (int) $this->round_id || !ctype_digit((string) $this->seat) || (int) $this->seat < 1 || (int) $this->seat > (int) $room->capacity || $room->venue !== $this->venue || $room->name !== $this->room) $this->addError($attribute, 'ข้อมูลที่นั่งต้องตรงกับห้องที่จัดไว้และไม่เกินความจุ'); } }],
    ]; }
    public function attributeLabels() { return ['exam_number' => 'เลขประจำตัวสอบ', 'venue' => 'สนามสอบ / อาคาร', 'room' => 'ห้องสอบ', 'seat' => 'เลขที่นั่งสอบ', 'note' => 'ข้อปฏิบัติ / หมายเหตุ', 'published' => 'เปิดให้ผู้สมัครพิมพ์บัตรประจำตัวสอบ']; }
}
