<?php
require dirname(__DIR__) . '/config/bootstrap.php';
new yii\console\Application(require dirname(__DIR__) . '/config/console.php');
$token = $argv[1] ?? '';
if (YII_ENV !== 'dev' || !preg_match('/^[a-z0-9]{5,20}$/', $token)) exit(1);
Yii::$app->db->transaction(function ($db) use ($token) {
    $user = app\models\User::findOne(['name' => "ผู้สมัครทดสอบระบบ $token", 'role' => 'applicant']);
    if ($user) {
        foreach (app\models\Application::findAll(['user_id' => $user->id]) as $application) {
            foreach ($application->documents as $document) {
                $path = Yii::getAlias('@app/runtime/application-documents/') . $document->stored_name;
                if (is_file($path)) unlink($path);
            }
            $application->delete();
        }
        $user->delete();
    }
    app\models\Subject::deleteAll(['code' => "TEST-$token", 'name' => "วิชาทดสอบ $token"]);
    app\models\Milestone::deleteAll(['title' => "ตรวจเอกสาร $token"]);
    $round = app\models\ExamRound::findOne(['name' => "TEST-ADMISSION-$token", 'is_example' => 0]);
    if ($round) {
        app\models\ExamRoom::deleteAll(['round_id' => $round->id]);
        app\models\AdmissionProgram::deleteAll(['round_id' => $round->id]);
        app\models\Subject::deleteAll(['round_id' => $round->id]);
        $round->delete();
    }
    app\models\User::deleteAll(['email' => array_map(fn($role) => "ops-$role-$token@example.test", ['admissions', 'finance', 'exam', 'committee', 'created'])]);
    foreach (app\models\ExamRound::findAll(['name' => ["TEST-SCHEDULE-$token", "WRONG-SCHEDULE-$token"]]) as $scheduleRound) $scheduleRound->delete();
});
