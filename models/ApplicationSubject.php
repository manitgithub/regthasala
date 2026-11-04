<?php
namespace app\models;
class ApplicationSubject extends \yii\db\ActiveRecord
{
    public static function tableName() { return 'application_subject'; }
    public function getSubject() { return $this->hasOne(Subject::class, ['id' => 'subject_id']); }
    public function getScore() { return $this->hasOne(ExamScore::class, ['item_id' => 'id']); }
    public function getApplication() { return $this->hasOne(Application::class, ['id' => 'application_id']); }
}
