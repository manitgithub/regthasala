<?php
namespace app\models;

class ExamNumberSettingsForm extends \yii\base\Model
{
    public $prefix = '';
    public $digits = 6;
    public $start = 1;

    public function rules() { return [
        ['prefix', 'trim'], ['prefix', 'string', 'max' => 30],
        ['prefix', 'match', 'pattern' => '/^[A-Za-z0-9-]*$/D', 'message' => 'ใช้ตัวอักษรอังกฤษ ตัวเลข หรือขีดกลางเท่านั้น'],
        [['digits', 'start'], 'required'], ['digits', 'integer', 'min' => 1, 'max' => 9],
        ['start', 'integer', 'min' => 1, 'max' => 999999999],
        ['start', function ($attribute) { if (!$this->hasErrors() && strlen((string) $this->start) > (int) $this->digits) $this->addError($attribute, 'เลขเริ่มต้นต้องไม่เกินจำนวนหลักที่กำหนด'); }],
    ]; }
}
