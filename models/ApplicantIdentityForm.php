<?php
namespace app\models;

class ApplicantIdentityForm extends \yii\base\Model
{
    public $name = '';
    public $national_id = '';
    public $birth_date = '';

    public function rules()
    {
        return [
            [['name', 'national_id', 'birth_date'], 'trim'],
            [['national_id', 'birth_date'], 'required'],
            ['name', 'required', 'on' => 'register'],
            ['name', 'string', 'max' => 190],
            ['national_id', 'match', 'pattern' => '/^[0-9]{13}$/D', 'message' => 'กรอกเลขบัตรประชาชน 13 หลัก'],
            ['national_id', function ($attribute) {
                if ($this->hasErrors($attribute)) return;
                $sum = 0;
                for ($i = 0; $i < 12; $i++) $sum += (int) $this->$attribute[$i] * (13 - $i);
                if (((11 - $sum % 11) % 10) !== (int) $this->$attribute[12]) $this->addError($attribute, 'เลขบัตรประชาชนไม่ถูกต้อง');
            }],
            ['birth_date', 'match', 'pattern' => '/^[0-9]{8}$/D', 'message' => 'กรอกวันเกิด 8 หลัก เช่น 01012540'],
            ['birth_date', function ($attribute) {
                if ($this->hasErrors($attribute)) return;
                $value = $this->$attribute;
                $day = (int) substr($value, 0, 2);
                $month = (int) substr($value, 2, 2);
                $year = (int) substr($value, 4, 4) - 543;
                if ($year < 1900 || !checkdate($month, $day, $year) || sprintf('%04d-%02d-%02d', $year, $month, $day) > date('Y-m-d')) {
                    $this->addError($attribute, 'วันเกิดไม่ถูกต้อง ใช้รูปแบบ ววดดปปปป ปี พ.ศ.');
                }
            }],
        ];
    }

    public function attributeLabels()
    {
        return ['name' => 'ชื่อและนามสกุล', 'national_id' => 'เลขบัตรประชาชน', 'birth_date' => 'วันเกิด 8 หลัก (พ.ศ.)'];
    }
}
