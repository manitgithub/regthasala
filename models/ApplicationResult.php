<?php
namespace app\models;
class ApplicationResult extends \yii\db\ActiveRecord
{
    public const OUTCOMES = ['pass' => 'ผ่านการคัดเลือก', 'reserve' => 'บัญชีสำรอง', 'fail' => 'ไม่ผ่านการคัดเลือก'];
    public static function tableName() { return 'application_result'; }
}
