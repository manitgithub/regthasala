<?php
namespace app\models;
class Application extends \yii\db\ActiveRecord
{
    public static function tableName() { return 'application'; }
    public function getRound() { return $this->hasOne(ExamRound::class, ['id' => 'round_id']); }
    public function getApplicant() { return $this->hasOne(User::class, ['id' => 'user_id']); }
    public function getItems() { return $this->hasMany(ApplicationSubject::class, ['application_id' => 'id']); }
    public function getProgram() { return $this->hasOne(AdmissionProgram::class, ['id' => 'program_id']); }
    public function getProfile() { return $this->hasOne(ApplicationProfile::class, ['application_id' => 'id']); }
    public function getDocuments() { return $this->hasMany(ApplicationDocument::class, ['application_id' => 'id']); }
    public function getPayment() { return $this->hasOne(ApplicationPayment::class, ['application_id' => 'id']); }
    public function getTicket() { return $this->hasOne(ExamTicket::class, ['application_id' => 'id']); }
    public function getResult() { return $this->hasOne(ApplicationResult::class, ['application_id' => 'id']); }
    public function getCurrentDocuments() { return $this->getDocuments()->andWhere(['is_current' => 1]); }
    public function isEligible(): bool { return $this->qualification_status === 'approved' && $this->payment?->status === 'verified'; }
}
