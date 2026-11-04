<?php
namespace app\models;
class ExamRound extends \yii\db\ActiveRecord
{
    public static function tableName() { return 'exam_round'; }
    public function rules() { return [
        [['name', 'exam_type_id', 'opens_at', 'closes_at', 'payment_due_at', 'status'], 'required'],
        ['name', 'trim'], ['name', 'string', 'max' => 190],
        [['description', 'required_documents'], 'string'],
        [['payment_bank', 'payment_account', 'payment_account_name', 'payment_instructions'], 'trim'],
        [['payment_bank', 'payment_account', 'payment_account_name'], 'string', 'max' => 190],
        ['payment_instructions', 'string', 'max' => 5000],
        [['payment_bank', 'payment_account', 'payment_account_name'], 'required', 'when' => fn($model) => $model->payment_bank || $model->payment_account || $model->payment_account_name],
        ['exam_type_id', 'exist', 'targetClass' => ExamType::class, 'targetAttribute' => 'id'],
        ['status', 'in', 'range' => ['draft', 'published', 'closed']],
        ['registration_mode', 'default', 'value' => 'subjects'],
        ['registration_mode', 'in', 'range' => ['subjects', 'admission']],
        [['opens_at', 'closes_at', 'payment_due_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
        ['closes_at', 'compare', 'compareAttribute' => 'opens_at', 'operator' => '>'],
        ['payment_due_at', 'compare', 'compareAttribute' => 'closes_at', 'operator' => '>='],
    ]; }
    public function attributeLabels() { return [
        'name' => 'ชื่อรอบสอบ', 'exam_type_id' => 'ประเภทการสอบ', 'opens_at' => 'เปิดรับสมัคร',
        'closes_at' => 'ปิดรับสมัคร', 'payment_due_at' => 'สิ้นสุดชำระเงิน', 'status' => 'สถานะ',
        'payment_bank' => 'ธนาคาร', 'payment_account' => 'เลขบัญชีรับเงิน', 'payment_account_name' => 'ชื่อบัญชีรับเงิน',
    ]; }
    public function getType() { return $this->hasOne(ExamType::class, ['id' => 'exam_type_id']); }
    public function getSubjects() { return $this->hasMany(Subject::class, ['round_id' => 'id'])->orderBy(['starts_at' => SORT_ASC]); }
    public function getMilestones() { return $this->hasMany(Milestone::class, ['round_id' => 'id'])->orderBy(['occurs_at' => SORT_ASC]); }
    public function getPrograms() { return $this->hasMany(AdmissionProgram::class, ['round_id' => 'id'])->with('subjects')->orderBy(['target_grade' => SORT_ASC, 'id' => SORT_ASC]); }
    public function isOpen(): bool { $now = date('Y-m-d H:i:s'); return $this->status === 'published' && $now >= $this->opens_at && $now <= $this->closes_at; }
    public function hasPaymentAccount(): bool { return (bool) ($this->payment_bank && $this->payment_account && $this->payment_account_name); }
}
