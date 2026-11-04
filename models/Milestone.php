<?php
namespace app\models;
class Milestone extends \yii\db\ActiveRecord
{
    public static function tableName() { return 'milestone'; }
    public function rules() { return [
        [['round_id', 'title', 'occurs_at'], 'required'], [['title', 'venue', 'note'], 'trim'], [['title', 'venue'], 'string', 'max' => 190], ['note', 'string', 'max' => 2000],
        [['occurs_at', 'ends_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
        ['ends_at', 'compare', 'compareAttribute' => 'occurs_at', 'operator' => '>=', 'message' => 'วันสิ้นสุดต้องไม่ก่อนวันเริ่ม'],
        ['round_id', 'exist', 'targetClass' => ExamRound::class, 'targetAttribute' => 'id'],
    ]; }
    public function attributeLabels() { return ['title' => 'ชื่อกำหนดการ', 'occurs_at' => 'วันและเวลา', 'ends_at' => 'วันและเวลาสิ้นสุด', 'venue' => 'สถานที่', 'note' => 'รายละเอียดเพิ่มเติม']; }
}
