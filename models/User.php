<?php
namespace app\models;

use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class User extends ActiveRecord implements IdentityInterface
{
    public static function tableName() { return 'user'; }
    public function beforeSave($insert)
    {
        if ($insert && $this->active === null) $this->active = true;
        return parent::beforeSave($insert);
    }
    public function rules() { return [
        [['name', 'password_hash', 'auth_key'], 'required'],
        ['email', 'email'], ['email', 'filter', 'filter' => 'strtolower', 'skipOnEmpty' => true],
        [['email', 'name'], 'string', 'max' => 190], ['email', 'unique'],
        ['national_id', 'match', 'pattern' => '/^[0-9]{13}$/D'], ['national_id', 'unique'],
    ]; }
    public static function findIdentity($id) { return static::findOne(['id' => $id, 'active' => 1]); }
    public static function findIdentityByAccessToken($token, $type = null) { return null; }
    public function getId() { return $this->id; }
    public function getAuthKey() { return $this->auth_key; }
    public function validateAuthKey($authKey) { return hash_equals($this->auth_key, $authKey); }
    public function isAdmin(): bool { return $this->role === 'admin'; }
    public const STAFF_ROLES = ['admin' => 'ผู้ดูแลระบบ', 'admissions' => 'เจ้าหน้าที่รับสมัคร', 'finance' => 'เจ้าหน้าที่การเงิน', 'exam' => 'เจ้าหน้าที่จัดสอบ', 'committee' => 'ผู้บริหาร / คณะกรรมการ'];
    public function isStaff(): bool { return isset(self::STAFF_ROLES[$this->role]); }
    public function can(string $permission): bool
    {
        if (!$this->active) return false;
        if ($this->isAdmin()) return true;
        $permissions = [
            'admissions' => ['rounds.view', 'applications.view', 'qualification.review', 'documents.review'],
            'finance' => ['rounds.view', 'applications.view', 'payments.review', 'reports.view'],
            'exam' => ['rounds.view', 'rounds.manage', 'applications.view', 'exam.manage', 'results.manage', 'results.view'],
            'committee' => ['rounds.view', 'results.view', 'results.publish', 'reports.view'],
        ];
        return in_array($permission, $permissions[$this->role] ?? [], true);
    }
    public function canViewDocument(string $kind): bool
    {
        return $this->isAdmin() || ($kind === 'payment_slip' ? $this->can('payments.review') : $this->can('documents.review') || ($kind === 'photo' && $this->can('exam.manage')));
    }
}
