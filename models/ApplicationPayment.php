<?php
namespace app\models;
class ApplicationPayment extends \yii\db\ActiveRecord
{
    public static function tableName() { return 'application_payment'; }
    public function getDocument() { return $this->hasOne(ApplicationDocument::class, ['id' => 'document_id']); }
}
