<?php
namespace app\models;
class StaffUserForm extends \yii\base\Model
{
    public $name = '';
    public $email = '';
    public $role = 'admissions';
    public $active = true;
    public $password = '';
    public function rules() { return [
        [['name', 'email'], 'trim'], [['name', 'email', 'role'], 'required'],
        [['name', 'email'], 'string', 'max' => 190], ['email', 'email'], ['email', 'filter', 'filter' => 'strtolower'],
        ['role', 'in', 'range' => array_keys(User::STAFF_ROLES)], ['active', 'boolean'],
        ['password', 'string', 'min' => 12, 'max' => 200], ['password', 'required', 'on' => 'create'],
    ]; }
    public function attributeLabels() { return ['name' => 'ชื่อผู้ใช้งาน', 'email' => 'อีเมลเจ้าหน้าที่', 'role' => 'บทบาท / สิทธิ์', 'active' => 'เปิดใช้งาน', 'password' => 'รหัสผ่าน (อย่างน้อย 12 ตัวอักษร)']; }
}
