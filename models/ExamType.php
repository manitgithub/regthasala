<?php
namespace app\models;
class ExamType extends \yii\db\ActiveRecord
{
    public static function tableName() { return 'exam_type'; }
    public function rules() { return [[['name'], 'required'], ['name', 'trim'], ['name', 'string', 'max' => 190], ['name', 'unique']]; }
    public function attributeLabels() { return ['name' => 'ชื่อประเภทการสอบ']; }
}
