<?php
namespace app\commands;

use app\models\{User, ExamType, ExamRound, Subject, Milestone};
use Yii;

class SeedController extends \yii\console\Controller
{
    public function actionIndex()
    {
        if (YII_ENV !== 'dev') { $this->stderr("Seed is only available in APP_ENV=dev.\n"); return 1; }
        if (User::find()->exists()) { $this->stdout("Seed skipped: users already exist.\n"); return 0; }
        $password = Yii::$app->security->generateRandomString(18);
        Yii::$app->db->transaction(function () use ($password) {
            $user = new User(['name' => 'ผู้ดูแลระบบ', 'email' => 'admin@examops.test', 'password_hash' => Yii::$app->security->generatePasswordHash($password), 'auth_key' => Yii::$app->security->generateRandomString(), 'role' => 'admin', 'created_at' => date('Y-m-d H:i:s')]);
            if (!$user->save()) throw new \RuntimeException(implode(', ', $user->getFirstErrors()));
            $type = new ExamType(['name' => 'สอบคัดเลือกบุคคลเข้าศึกษา']); $type->save(false);
            $round = new ExamRound([
                'exam_type_id' => $type->id, 'name' => 'รอบที่ 1 · รับสมัครทั่วไป',
                'description' => 'การสอบคัดเลือกประจำปีการศึกษา ' . ((int) date('Y') + 543),
                'opens_at' => date('Y-m-d 08:30:00', strtotime('-1 day')), 'closes_at' => date('Y-m-d 16:30:00', strtotime('+20 days')),
                'payment_due_at' => date('Y-m-d 23:59:00', strtotime('+22 days')), 'status' => 'published',
                'required_documents' => "รูปถ่ายหน้าตรง\nสำเนาบัตรประชาชน\nหลักฐานการศึกษา",
            ]); $round->save(false);
            foreach ([['TH101', 'ภาษาไทย', '09:00:00', '10:30:00', 250], ['EN201', 'ภาษาอังกฤษ', '11:00:00', '12:30:00', 300], ['MT301', 'คณิตศาสตร์', '13:30:00', '15:00:00', 350]] as [$code, $name, $start, $end, $fee]) {
                (new Subject(['round_id' => $round->id, 'code' => $code, 'name' => $name,
                    'starts_at' => date('Y-m-d', strtotime('+30 days')) . ' ' . $start, 'ends_at' => date('Y-m-d', strtotime('+30 days')) . ' ' . $end,
                    'capacity' => 240, 'fee' => $fee, 'venue' => 'อาคาร A · ห้อง 301', 'status' => 'open',
                ]))->save(false);
            }
            foreach ([['ประกาศรายชื่อผู้มีสิทธิ์สอบ', 25], ['วันสอบ', 30], ['ประกาศผลสอบ', 40]] as [$title, $days]) {
                (new Milestone(['round_id' => $round->id, 'title' => $title, 'occurs_at' => date('Y-m-d 09:00:00', strtotime("+$days days"))]))->save(false);
            }
        });
        $path = Yii::getAlias('@app/runtime/dev-access.txt');
        file_put_contents($path, "Email: admin@examops.test\nPassword: $password\n"); chmod($path, 0600);
        $this->stdout("Seed complete. Local admin credentials: runtime/dev-access.txt\n");
        return 0;
    }
}
