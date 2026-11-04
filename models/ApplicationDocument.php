<?php
namespace app\models;
class ApplicationDocument extends \yii\db\ActiveRecord
{
    public static function tableName() { return 'application_document'; }
    public function getApplication() { return $this->hasOne(Application::class, ['id' => 'application_id']); }
}
