<?php
require dirname(__DIR__) . '/config/bootstrap.php';
new yii\console\Application(require dirname(__DIR__) . '/config/console.php');
use app\models\{ExamRound, Subject, AdmissionProgram};
$token = $argv[1] ?? '';
if (YII_ENV !== 'dev' || !preg_match('/^[a-z0-9]{5,20}$/', $token)) exit(1);
$result = Yii::$app->db->transaction(function ($db) use ($token) {
    $source = ExamRound::find()->select('exam_round.*')->innerJoin('admission_program p', 'p.round_id=exam_round.id')->where(['registration_mode' => 'admission', 'p.target_grade' => 'm4', 'p.code' => 'DEP'])->andWhere(['not like', 'exam_round.name', 'TEST-'])->orderBy(['exam_round.id' => SORT_DESC])->one();
    if (!$source) throw new RuntimeException('Run php yii admission-example first');
    $round = new ExamRound($source->attributes); $round->id = null;
    $round->name = "TEST-ADMISSION-$token"; $round->is_example = false; $round->status = 'published';
    $round->payment_bank = 'ธนาคารทดสอบ'; $round->payment_account = '000-TEST-000'; $round->payment_account_name = 'บัญชีทดสอบระบบ ไม่ใช่บัญชีรับเงินจริง';
    $round->opens_at = date('Y-m-d H:i:s', time() - 3600); $round->closes_at = date('Y-m-d H:i:s', time() + 3600); $round->payment_due_at = date('Y-m-d H:i:s', time() + 7200); $round->save(false);
    $map = [];
    foreach ($source->subjects as $original) {
        $subject = new Subject($original->attributes); $subject->id = null; $subject->round_id = $round->id; $subject->save(false); $map[$original->id] = $subject->id;
    }
    $programs = [];
    foreach ($source->programs as $original) {
        $program = new AdmissionProgram($original->attributes); $program->id = null; $program->round_id = $round->id; $program->save(false);
        foreach ($original->subjects as $subject) $db->createCommand()->insert('program_subject', ['program_id' => $program->id, 'subject_id' => $map[$subject->id]])->execute();
        $programs[$program->code] = $program->id;
    }
    return ['roundId' => $round->id, 'programs' => $programs];
});
echo json_encode($result);
