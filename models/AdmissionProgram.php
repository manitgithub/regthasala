<?php
namespace app\models;
class AdmissionProgram extends \yii\db\ActiveRecord
{
    public static function tableName() { return 'admission_program'; }
    public function rules() { return [
        [['round_id', 'code', 'name', 'target_grade', 'academic_year', 'intake', 'gpa_basis', 'minimum_gpa', 'application_fee', 'status'], 'required'],
        [['code', 'name', 'gpa_basis'], 'trim'], ['code', 'string', 'max' => 30], [['name', 'gpa_basis'], 'string', 'max' => 190],
        ['target_grade', 'in', 'range' => ['m1', 'm4']], ['status', 'in', 'range' => ['open', 'closed']],
        ['academic_year', 'integer', 'min' => 2500, 'max' => 2700], ['intake', 'integer', 'min' => 1, 'max' => 100000],
        [['minimum_gpa', 'minimum_foreign_gpa'], 'number', 'min' => 0, 'max' => 4],
        [['application_fee', 'enrollment_fee'], 'number', 'min' => 0, 'max' => 99999999.99],
        ['round_id', 'exist', 'targetClass' => ExamRound::class, 'targetAttribute' => 'id'],
        ['code', 'unique', 'targetAttribute' => ['round_id', 'code']],
    ]; }
    public function getSubjects() { return $this->hasMany(Subject::class, ['id' => 'subject_id'])->viaTable('program_subject', ['program_id' => 'id'])->orderBy('starts_at'); }
    public function getGradeLabel(): string { return $this->target_grade === 'm1' ? 'ม.1' : 'ม.4'; }
    public function getSnapshot(): array { return array_intersect_key($this->attributes, array_flip(['code', 'name', 'target_grade', 'academic_year', 'gpa_basis', 'minimum_gpa', 'minimum_foreign_gpa', 'application_fee', 'enrollment_fee'])); }
}
