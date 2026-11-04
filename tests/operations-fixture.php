<?php
ob_start(); require __DIR__ . '/admission-fixture.php'; $fixture = json_decode(ob_get_clean(), true);
use app\models\{User, Application, AdmissionProgram};
$token = $argv[1];
$password = 'OpsTest-Only-2026!';
$base = '997' . substr((string) (int) (microtime(true) * 1000), -9); $sum = 0;
for ($i = 0; $i < 12; $i++) $sum += (int) $base[$i] * (13 - $i);
$nationalId = $base . ((11 - $sum % 11) % 10);
$user = (new \app\services\ApplicantAccountService())->registerOrAuthenticate(new \app\models\ApplicantIdentityForm(['scenario' => 'register', 'national_id' => $nationalId, 'birth_date' => '01012553', 'name' => "ผู้สมัครทดสอบระบบ $token"]));
$program = AdmissionProgram::findOne($fixture['programs']['DEP']);
$app = new Application(['reference' => 'TEST-OPS-' . $token, 'user_id' => $user->id, 'round_id' => $fixture['roundId'], 'program_id' => $program->id, 'application_fee' => 100, 'total' => 100, 'status' => 'payment_review', 'created_at' => date('Y-m-d H:i:s'), 'admission_snapshot' => $program->getSnapshot()]); $app->save(false);
$db = Yii::$app->db;
foreach ($program->subjects as $subject) $db->createCommand()->insert('application_subject', ['application_id' => $app->id, 'subject_id' => $subject->id, 'fee' => 0, 'payment_status' => 'unpaid'])->execute();
$profile = ['prefix' => 'นาย', 'applicant_name' => $user->name, 'phone' => '0800000000', 'email' => '', 'address' => 'ข้อมูลทดสอบ', 'district' => 'ท่าศาลา', 'province' => 'นครศรีธรรมราช', 'postal_code' => '80160', 'school_name' => 'โรงเรียนทดสอบ', 'school_province' => 'นครศรีธรรมราช', 'education_level' => 'm3', 'education_status' => 'studying', 'graduation_year' => 2569, 'gpa' => '3.50', 'foreign_gpa' => '3.50', 'guardian_name' => 'ผู้ปกครองทดสอบ', 'guardian_relationship' => 'มารดา', 'guardian_phone' => '0800000001'];
$profile['subdistrict_code'] = '800801'; $profile['subdistrict'] = 'ท่าศาลา';
$db->createCommand()->insert('application_profile', ['application_id' => $app->id, 'data' => new yii\db\JsonExpression($profile)])->execute();
foreach (['photo', 'payment_slip'] as $kind) {
    $name = "test-$token-$kind.png";
    $path = Yii::getAlias('@app/runtime/application-documents/') . $name;
    file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a1ZkAAAAASUVORK5CYII='));
    $db->createCommand()->insert('application_document', ['application_id' => $app->id, 'kind' => $kind, 'stored_name' => $name, 'original_name' => "$kind.png", 'mime_type' => 'image/png', 'size' => filesize($path)])->execute();
    $documents[$kind] = (int) $db->getLastInsertID();
}
$db->createCommand()->insert('application_payment', ['application_id' => $app->id, 'document_id' => $documents['payment_slip'], 'amount' => 100, 'transferred_at' => date('Y-m-d H:i:s'), 'status' => 'pending_review', 'account_snapshot' => new yii\db\JsonExpression(['bank' => 'test']), 'created_at' => date('Y-m-d H:i:s')])->execute();
foreach (['admissions', 'finance', 'exam', 'committee'] as $role) {
    $staff = new User(['email' => "ops-$role-$token@example.test", 'name' => "เจ้าหน้าที่ทดสอบ $role $token", 'password_hash' => Yii::$app->security->generatePasswordHash($password), 'auth_key' => Yii::$app->security->generateRandomString(), 'role' => $role, 'active' => 1, 'created_at' => date('Y-m-d H:i:s')]); $staff->save(false); $staffIds[$role] = $staff->id;
}
echo json_encode($fixture + ['applicationId' => $app->id, 'nationalId' => $nationalId, 'password' => $password, 'documents' => $documents, 'staffIds' => $staffIds, 'subjectIds' => array_column($program->subjects, 'id')]);
