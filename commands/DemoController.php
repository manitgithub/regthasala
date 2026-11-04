<?php
namespace app\commands;

use app\models\{Application, ExamRoom, ExamRound, User};
use app\services\AuditService;
use Yii;
use yii\db\JsonExpression;

class DemoController extends \yii\console\Controller
{
    public function actionRooms(int $round = 5): int
    {
        if (YII_ENV !== 'dev') {
            $this->stderr("Demo seed is restricted to APP_ENV=dev.\n");
            return 1;
        }
        $model = ExamRound::find()->where(['id' => $round])->with('programs.subjects')->one();
        $programs = array_values(array_filter($model?->programs ?? [], static fn ($p) => $p->status === 'open'));
        if (!$model || !$programs) {
            $this->stderr("Round must exist and have open admission programs.\n");
            return 1;
        }
        $createdFiles = [];
        $created = 0;
        $roomsCreated = 0;
        try {
            Yii::$app->db->transaction(function ($db) use ($round, $programs, &$createdFiles, &$created, &$roomsCreated) {
                $db->createCommand('SELECT id FROM exam_round WHERE id=:id FOR UPDATE', [':id' => $round])->queryScalar();
                for ($i = 1; $i <= 3; $i++) {
                    $attributes = ['round_id' => $round, 'venue' => 'อาคารทดสอบระบบ', 'name' => 'ห้องทดสอบ ' . (100 + $i)];
                    if (ExamRoom::find()->where($attributes)->exists()) continue;
                    $room = new ExamRoom($attributes + ['capacity' => 10]);
                    if (!$room->save()) throw new \RuntimeException(implode(' ', $room->getFirstErrors()));
                    $roomsCreated++;
                }
                for ($i = 1; $i <= 15; $i++) {
                    $reference = sprintf('DEMO-R%d-%03d', $round, $i);
                    if (Application::find()->where(['reference' => $reference])->exists()) continue;
                    $email = strtolower($reference) . '@example.test';
                    if (User::find()->where(['email' => $email])->exists()) throw new \RuntimeException('Demo email already in use; no existing account was changed.');
                    $now = date('Y-m-d H:i:s');
                    $user = new User([
                        'name' => sprintf('ผู้สมัครทดสอบ %02d รอบ %d', $i, $round), 'email' => $email,
                        'password_hash' => Yii::$app->security->generatePasswordHash(Yii::$app->security->generateRandomString()),
                        'auth_key' => Yii::$app->security->generateRandomString(), 'role' => 'applicant', 'created_at' => $now,
                    ]);
                    if (!$user->save()) throw new \RuntimeException(implode(' ', $user->getFirstErrors()));
                    $program = $programs[($i - 1) % count($programs)];
                    $eligible = $i <= 12;
                    $app = new Application([
                        'reference' => $reference, 'user_id' => $user->id, 'round_id' => $round,
                        'program_id' => $program->id, 'admission_snapshot' => $program->getSnapshot(),
                        'application_fee' => $program->application_fee, 'total' => $program->application_fee,
                        'status' => $eligible ? 'eligible' : 'payment_review',
                        'qualification_status' => $eligible ? 'approved' : 'pending',
                        'qualification_reason' => 'ข้อมูลทดสอบจำลอง ไม่ใช่ผลพิจารณาผู้สมัครจริง', 'created_at' => $now,
                    ]);
                    if (!$app->save(false)) throw new \RuntimeException('Unable to create demo application.');
                    foreach ($program->subjects as $subject) {
                        $db->createCommand()->insert('application_subject', ['application_id' => $app->id, 'subject_id' => $subject->id, 'fee' => 0, 'payment_status' => $eligible ? 'paid' : 'unpaid'])->execute();
                    }
                    $profile = [
                        'prefix' => 'นาย', 'applicant_name' => $user->name, 'email' => $email,
                        'address' => 'ที่อยู่ทดสอบระบบ', 'subdistrict_code' => '800801', 'subdistrict' => 'ท่าศาลา',
                        'district' => 'ท่าศาลา', 'province' => 'นครศรีธรรมราช', 'postal_code' => '80160',
                        'school_name' => 'โรงเรียนทดสอบระบบ', 'school_province' => 'นครศรีธรรมราช',
                        'education_level' => $program->target_grade === 'm1' ? 'p6' : 'm3',
                        'education_status' => 'studying', 'graduation_year' => $program->academic_year,
                        'gpa' => '3.50', 'foreign_gpa' => '3.50', 'guardian_name' => 'ผู้ปกครองทดสอบ',
                        'guardian_relationship' => 'บิดา', 'phone' => '', 'guardian_phone' => '',
                    ];
                    $db->createCommand()->insert('application_profile', ['application_id' => $app->id, 'data' => new JsonExpression($profile)])->execute();
                    $stored = strtolower($reference) . '-' . Yii::$app->security->generateRandomString(8) . '.txt';
                    $path = Yii::getAlias('@app/runtime/application-documents/') . $stored;
                    $content = "TEST DATA ONLY - NOT A PAYMENT RECEIPT\n$reference\nNo actual bank transfer occurred.\n";
                    if (file_put_contents($path, $content) === false) throw new \RuntimeException('Unable to write demo attachment.');
                    $createdFiles[] = $path;
                    $db->createCommand()->insert('application_document', [
                        'application_id' => $app->id, 'kind' => 'payment_slip', 'stored_name' => $stored,
                        'original_name' => 'TEST-NOT-A-RECEIPT.txt', 'mime_type' => 'text/plain', 'size' => strlen($content),
                        'review_status' => $eligible ? 'accepted' : 'pending', 'review_reason' => 'เอกสารจำลองทดสอบระบบเท่านั้น',
                    ])->execute();
                    $documentId = $db->getLastInsertID();
                    $db->createCommand()->insert('application_payment', [
                        'application_id' => $app->id, 'document_id' => $documentId, 'amount' => $program->application_fee,
                        'transferred_at' => $now, 'transfer_reference' => $reference,
                        'status' => $eligible ? 'verified' : 'pending_review',
                        'review_reason' => 'ยอดจำลองทดสอบ ไม่มีการโอนเงินจริง',
                        'account_snapshot' => new JsonExpression(['bank' => 'ข้อมูลทดสอบ ไม่มีการโอนเงินจริง', 'account' => '', 'account_name' => 'ทดสอบระบบ']), 'created_at' => $now,
                    ])->execute();
                    $created++;
                }
                if ($created || $roomsCreated) AuditService::record('demo.seed', 'exam_round', $round, ['applications_created' => $created, 'rooms_created' => $roomsCreated, 'synthetic' => true]);
            });
        } catch (\Throwable $e) {
            foreach ($createdFiles as $path) if (is_file($path)) unlink($path);
            throw $e;
        }
        $this->stdout("Round $round: created $roomsCreated demo rooms and $created demo applications.\n12 eligible, 3 pending on first run. No seats allocated or tickets published.\nSynthetic payments affect development reports; no real transfer occurred.\n");
        return 0;
    }
}
