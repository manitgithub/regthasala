<?php
namespace app\models;
class AdmissionForm extends \yii\base\Model
{
    public $program_id;
    public $prefix = '';
    public $applicant_name = '';
    public $phone = '';
    public $email = '';
    public $address = '';
    public $subdistrict = '';
    public $subdistrict_code = '';
    public $district = '';
    public $province = '';
    public $postal_code = '';
    public $school_name = '';
    public $school_province = '';
    public $education_level = '';
    public $education_status = '';
    public $graduation_year;
    public $gpa;
    public $foreign_gpa;
    public $guardian_name = '';
    public $guardian_relationship = '';
    public $guardian_phone = '';
    public $confirmed = false;
    public $photo;
    public $id_copy;
    public $house_copy;
    public $transcript;
    public $name_change;

    public const DOCUMENTS = ['photo' => 'รูปถ่ายนักเรียน', 'id_copy' => 'สำเนาบัตรประชาชนนักเรียน', 'house_copy' => 'สำเนาทะเบียนบ้านนักเรียน', 'transcript' => 'ปพ.1 หรือใบรับรองผลการเรียน', 'name_change' => 'หลักฐานเปลี่ยนชื่อ–สกุล (ถ้ามี)'];
    public const APPLICATION_DOCUMENTS = ['photo' => 'รูปถ่ายนักเรียน'];
    public const ENROLLMENT_DOCUMENTS = ['id_copy' => 'สำเนาบัตรประชาชนนักเรียน', 'house_copy' => 'สำเนาทะเบียนบ้านนักเรียน', 'transcript' => 'ปพ.1 หรือใบรับรองผลการเรียน', 'name_change' => 'หลักฐานเปลี่ยนชื่อ–สกุล (ถ้ามี)'];
    public const LEVELS = ['p6' => 'ประถมศึกษาปีที่ 6', 'p6_equivalent' => 'เทียบเท่าประถมศึกษาปีที่ 6', 'm3' => 'มัธยมศึกษาปีที่ 3', 'm3_equivalent' => 'เทียบเท่ามัธยมศึกษาปีที่ 3'];

    public function rules() { return [
        ['subdistrict_code', 'required', 'message' => 'กรุณาค้นหาและเลือกตำบล อำเภอ จังหวัดจากรายการ'],
        ['subdistrict_code', function ($attribute) {
            $location = \app\services\ThaiAddressCatalog::find($this->$attribute);
            if (!$location) { $this->addError($attribute, 'พื้นที่ที่เลือกไม่ถูกต้อง กรุณาเลือกจากรายการอีกครั้ง'); return; }
            foreach ($location as $name => $value) $this->$name = $value;
        }],
        [['prefix', 'applicant_name', 'phone', 'email', 'address', 'district', 'province', 'postal_code', 'school_name', 'school_province', 'guardian_name', 'guardian_relationship', 'guardian_phone'], 'trim'],
        [['program_id', 'prefix', 'applicant_name', 'phone', 'address', 'district', 'province', 'postal_code', 'school_name', 'school_province', 'education_level', 'education_status', 'graduation_year', 'gpa', 'guardian_name', 'guardian_relationship', 'guardian_phone'], 'required'],
        ['program_id', 'integer', 'min' => 1],
        ['prefix', 'in', 'range' => ['เด็กชาย', 'เด็กหญิง', 'นาย', 'นางสาว', 'อื่น ๆ']],
        [['applicant_name', 'email', 'school_name', 'guardian_name'], 'string', 'max' => 190],
        [['district', 'province', 'school_province', 'guardian_relationship'], 'string', 'max' => 100],
        ['address', 'string', 'max' => 1000], ['email', 'email'],
        [['phone', 'guardian_phone'], 'match', 'pattern' => '/^0[0-9]{8,9}$/D', 'message' => 'กรอกเบอร์โทรศัพท์ 9–10 หลัก เริ่มด้วย 0'],
        ['postal_code', 'match', 'pattern' => '/^[0-9]{5}$/D'],
        ['education_level', 'in', 'range' => array_keys(self::LEVELS)],
        ['education_status', 'in', 'range' => ['studying', 'graduated']],
        ['graduation_year', 'integer', 'min' => 2500, 'max' => 2700],
        [['gpa', 'foreign_gpa'], 'match', 'pattern' => '/^[0-4](?:\.[0-9]{1,2})?$/D', 'message' => 'กรอกเกรด 0.00–4.00 ไม่เกิน 2 ตำแหน่ง'],
        [['gpa', 'foreign_gpa'], 'number', 'min' => 0, 'max' => 4],
        ['confirmed', 'compare', 'compareValue' => '1', 'message' => 'กรุณารับรองความถูกต้องของข้อมูล', 'skipOnEmpty' => false],
        [['photo'], 'file', 'skipOnEmpty' => false, 'extensions' => ['jpg', 'jpeg', 'png'], 'mimeTypes' => ['image/jpeg', 'image/png'], 'maxSize' => 5 * 1024 * 1024, 'except' => ['correction']],
        [['photo'], 'file', 'skipOnEmpty' => true, 'extensions' => ['jpg', 'jpeg', 'png'], 'mimeTypes' => ['image/jpeg', 'image/png'], 'maxSize' => 5 * 1024 * 1024, 'on' => ['correction']],
        [['id_copy', 'house_copy', 'transcript'], 'file', 'skipOnEmpty' => true, 'extensions' => ['jpg', 'jpeg', 'png', 'pdf'], 'mimeTypes' => ['image/jpeg', 'image/png', 'application/pdf'], 'maxSize' => 5 * 1024 * 1024],
        [['name_change'], 'file', 'skipOnEmpty' => true, 'extensions' => ['jpg', 'jpeg', 'png', 'pdf'], 'mimeTypes' => ['image/jpeg', 'image/png', 'application/pdf'], 'maxSize' => 5 * 1024 * 1024],
    ]; }
    public function checkProgram(AdmissionProgram $program): bool
    {
        $valid = true;
        $levels = $program->target_grade === 'm1' ? ['p6', 'p6_equivalent'] : ['m3', 'm3_equivalent'];
        if (!in_array($this->education_level, $levels, true)) { $this->addError('education_level', 'ระดับการศึกษาไม่ตรงกับโครงการที่เลือก'); $valid = false; }
        if ((float) $this->gpa < (float) $program->minimum_gpa) { $this->addError('gpa', 'เกรดเฉลี่ยต่ำกว่าเกณฑ์ ' . $program->minimum_gpa); $valid = false; }
        if ($program->minimum_foreign_gpa !== null && ($this->foreign_gpa === null || $this->foreign_gpa === '' || (float) $this->foreign_gpa < (float) $program->minimum_foreign_gpa)) {
            $this->addError('foreign_gpa', 'โครงการนี้กำหนดเกรดภาษาต่างประเทศไม่น้อยกว่า ' . $program->minimum_foreign_gpa); $valid = false;
        }
        return $valid;
    }
    public function getProfileData(): array
    {
        return $this->getAttributes(['prefix', 'applicant_name', 'phone', 'email', 'address', 'subdistrict_code', 'subdistrict', 'district', 'province', 'postal_code', 'school_name', 'school_province', 'education_level', 'education_status', 'graduation_year', 'gpa', 'foreign_gpa', 'guardian_name', 'guardian_relationship', 'guardian_phone']);
    }
    public function attributeLabels() { return [
        'program_id' => 'โครงการ', 'prefix' => 'คำนำหน้า', 'applicant_name' => 'ชื่อและนามสกุลผู้สมัคร', 'phone' => 'เบอร์โทรศัพท์ผู้สมัคร', 'email' => 'อีเมล',
        'address' => 'บ้านเลขที่ หมู่ ซอย ถนน', 'subdistrict_code' => 'พื้นที่ที่อยู่', 'subdistrict' => 'ตำบล / แขวง', 'district' => 'อำเภอ / เขต', 'province' => 'จังหวัด', 'postal_code' => 'รหัสไปรษณีย์',
        'school_name' => 'โรงเรียนเดิม / โรงเรียนที่กำลังศึกษา', 'school_province' => 'จังหวัดของโรงเรียน', 'education_level' => 'ระดับการศึกษา', 'education_status' => 'สถานะการศึกษา',
        'graduation_year' => 'ปีการศึกษาที่จบ / คาดว่าจะจบ (พ.ศ.)', 'gpa' => 'เกรดเฉลี่ยตามช่วงชั้นที่กำหนด', 'foreign_gpa' => 'เกรดเฉลี่ยกลุ่มสาระภาษาต่างประเทศ',
        'guardian_name' => 'ชื่อ–นามสกุลผู้ปกครอง', 'guardian_relationship' => 'ความสัมพันธ์กับผู้สมัคร', 'guardian_phone' => 'เบอร์โทรศัพท์ผู้ปกครอง',
    ] + self::DOCUMENTS; }
}
