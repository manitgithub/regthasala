<?php
namespace app\services;

use app\models\{ApplicantIdentityForm, User};
use Yii;

class ApplicantAccountService
{
    public function authenticate(ApplicantIdentityForm $form): User
    {
        $user = User::findOne(['national_id' => $form->national_id, 'role' => 'applicant', 'active' => 1]);
        $hash = $user->birth_date_hash ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        $valid = Yii::$app->security->validatePassword($form->birth_date, $hash);
        if (!$valid || !$user || !$user->birth_date_hash) throw new \DomainException('เลขบัตรประชาชนหรือวันเกิดไม่ถูกต้อง');
        return $user;
    }

    public function registerOrAuthenticate(ApplicantIdentityForm $form): User
    {
        if (User::find()->where(['national_id' => $form->national_id])->exists()) return $this->authenticate($form);
        $user = new User([
            'name' => $form->name, 'national_id' => $form->national_id,
            'birth_date_hash' => Yii::$app->security->generatePasswordHash($form->birth_date),
            'password_hash' => Yii::$app->security->generatePasswordHash(Yii::$app->security->generateRandomString(48)),
            'auth_key' => Yii::$app->security->generateRandomString(), 'role' => 'applicant',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        try {
            if (!$user->save()) throw new \DomainException(implode(' ', $user->getFirstErrors()));
        } catch (\yii\db\IntegrityException $e) {
            throw new \DomainException('ไม่สามารถสร้างบัญชีได้ กรุณาตรวจสอบข้อมูลหรือลองเข้าสู่ระบบ');
        }
        return $user;
    }
}
