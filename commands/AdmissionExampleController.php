<?php
namespace app\commands;
use app\models\{ExamType, ExamRound, Subject, AdmissionProgram, Milestone, Application};
use Yii;

class AdmissionExampleController extends \yii\console\Controller
{
    public function actionIndex()
    {
        if (YII_ENV !== 'dev') return 1;
        Yii::$app->db->transaction(function () {
            $type = ExamType::findOne(['name' => 'รับนักเรียนห้องเรียนพิเศษ']) ?? new ExamType(['name' => 'รับนักเรียนห้องเรียนพิเศษ']);
            if (!$type->save()) throw new \RuntimeException('Cannot create exam type');
            foreach (['m1' => 'ม.1', 'm4' => 'ม.4'] as $grade => $label) {
                $name = 'ตัวอย่างรับนักเรียน ' . $label . ' · ห้องเรียนพิเศษ 2569';
                $liveName = 'รับนักเรียน ' . $label . ' · ห้องเรียนพิเศษ 2569';
                if (ExamRound::find()->where(['name' => [$name, $liveName], 'registration_mode' => 'admission'])->exists()) continue;
                $round = new ExamRound([
                    'name' => $name, 'description' => 'อ้างอิงประกาศเดิม โรงเรียนท่าศาลาประสิทธิ์ศึกษา ใช้สำหรับตรวจแบบฟอร์ม',
                    'exam_type_id' => $type->id, 'registration_mode' => 'admission', 'is_example' => true, 'status' => 'draft',
                    'opens_at' => '2026-02-09 08:30:00', 'closes_at' => '2026-02-13 15:30:00', 'payment_due_at' => '2026-02-13 15:30:00',
                    'required_documents' => "รูปถ่ายนักเรียน\nสำเนาบัตรประชาชนนักเรียน\nสำเนาทะเบียนบ้านนักเรียน\nปพ.1 หรือใบรับรองผลการเรียน\nหลักฐานเปลี่ยนชื่อ–สกุล (ถ้ามี)",
                ]); $round->save(false);
                $date = $grade === 'm1' ? '2026-02-21' : '2026-02-22';
                $subjects = [];
                foreach ([['MA', 'คณิตศาสตร์', '09:00', '10:00'], ['EN', 'ภาษาอังกฤษ', '10:05', '10:45'], ['SC', 'วิทยาศาสตร์และเทคโนโลยี', '10:50', '11:30'], ['TH', 'ภาษาไทย', '12:30', '13:10'], ['SO', 'สังคมศึกษา', '13:15', '13:55'], ['CN', 'ภาษาจีน', '14:00', '14:40'], ['TS', 'ภาษาไทยและสังคมศึกษา', '12:30', '13:10']] as [$code, $title, $start, $end]) {
                    if ($grade === 'm1' && in_array($code, ['CN', 'TS'])) continue;
                    $subject = new Subject(['round_id' => $round->id, 'code' => $code, 'name' => $title, 'starts_at' => "$date $start:00", 'ends_at' => "$date $end:00", 'capacity' => 10000, 'fee' => 0, 'venue' => 'โรงเรียนท่าศาลาประสิทธิ์ศึกษา · รอจัดห้องสอบ', 'status' => 'open']);
                    $subject->save(false); $subjects[$code] = $subject;
                }
                $programs = $grade === 'm1' ? [
                    ['EP', 'โครงการหลักสูตรภาคภาษาอังกฤษ', '2.75', null, 'ป.4–ป.5', '16730.00', ['MA','EN','SC','TH','SO']],
                    ['SMTP', 'โครงการส่งเสริมอัจฉริยภาพด้านวิทยาศาสตร์ คณิตศาสตร์ และเทคโนโลยี', '3.00', null, 'ป.4–ป.5', '8430.00', ['MA','EN','SC','TH','SO']],
                ] : [
                    ['DEP', 'โครงการห้องเรียนพิเศษทางภาษาอังกฤษ', '2.50', '2.75', 'มัธยมศึกษาตอนต้น 5 ภาคเรียน', '9230.00', ['MA','EN','SC','TH','SO']],
                    ['CNP', 'โครงการห้องเรียนพิเศษทางภาษาจีน', '2.50', null, 'มัธยมศึกษาตอนต้น 5 ภาคเรียน', '4830.00', ['MA','EN','SC','TH','SO','CN']],
                    ['SMTP', 'โครงการห้องเรียนส่งเสริมความเป็นเลิศด้านวิทยาศาสตร์ คณิตศาสตร์ และเทคโนโลยี', '3.00', null, 'มัธยมศึกษาตอนต้น 5 ภาคเรียน', '10000.00', ['MA','EN','SC','TS']],
                ];
                foreach ($programs as [$code, $title, $gpa, $foreign, $basis, $enrollment, $codes]) {
                    $program = new AdmissionProgram(['round_id' => $round->id, 'code' => $code, 'name' => $title, 'target_grade' => $grade, 'academic_year' => 2569, 'intake' => 30, 'gpa_basis' => $basis, 'minimum_gpa' => $gpa, 'minimum_foreign_gpa' => $foreign, 'application_fee' => 100, 'enrollment_fee' => $enrollment, 'status' => 'open']);
                    $program->save(false);
                    foreach ($codes as $code) Yii::$app->db->createCommand()->insert('program_subject', ['program_id' => $program->id, 'subject_id' => $subjects[$code]->id])->execute();
                }
                $results = $grade === 'm1' ? '2026-02-26' : '2026-02-27';
                foreach ([['ประกาศรายชื่อผู้มีสิทธิ์สอบ', '2026-02-18'], ['วันสอบ', $date], ['ประกาศผลและรายงานตัว', $results], ['มอบตัวและชำระเงินบำรุงการศึกษา', $grade === 'm1' ? '2026-02-28' : '2026-03-01']] as [$title, $day]) {
                    (new Milestone(['round_id' => $round->id, 'title' => $title, 'occurs_at' => "$day 08:00:00"]))->save(false);
                }
            }
        });
        foreach (ExamRound::findAll(['is_example' => 1]) as $round) $this->stdout($round->name . ' → /site/admission-preview?id=' . $round->id . "\n");
        return 0;
    }
    public function actionActivate()
    {
        if (YII_ENV !== 'dev') return 1;
        $today = new \DateTimeImmutable('today', new \DateTimeZone('Asia/Bangkok'));
        $rounds = ExamRound::findAll(['is_example' => 1, 'registration_mode' => 'admission']);
        Yii::$app->db->transaction(function ($db) use ($rounds, $today) {
            foreach ($rounds as $round) {
                $db->createCommand('SELECT id FROM exam_round WHERE id=:id FOR UPDATE', [':id' => $round->id])->queryScalar();
                if (Application::find()->where(['round_id' => $round->id])->exists()) throw new \RuntimeException('Cannot reschedule a round that already has applications.');
                $grade = $round->programs[0]->target_grade ?? null;
                if (!in_array($grade, ['m1', 'm4'], true)) throw new \RuntimeException('Admission round has no grade/program.');
                $label = $grade === 'm1' ? 'ม.1' : 'ม.4';
                $year = (int) $today->format('Y') + 543;
                $examDay = $today->modify($grade === 'm1' ? '+30 days' : '+31 days');
                $round->name = 'รับนักเรียน ' . $label . ' · ห้องเรียนพิเศษ ' . $year;
                $round->description = 'โรงเรียนท่าศาลาประสิทธิ์ศึกษา · เลือกโครงการ กรอกข้อมูลการศึกษา และแนบเอกสารประกอบการสมัคร';
                $round->opens_at = $today->format('Y-m-d') . ' 00:00:00';
                $round->closes_at = $today->modify('+20 days')->format('Y-m-d') . ' 16:30:00';
                $round->payment_due_at = $today->modify('+22 days')->format('Y-m-d') . ' 23:59:00';
                $round->is_example = false;
                $round->status = 'published';
                if (!$round->save()) throw new \RuntimeException(implode(' ', $round->getFirstErrors()));
                foreach ($round->subjects as $subject) {
                    foreach (['starts_at', 'ends_at'] as $attribute) {
                        $date = new \DateTimeImmutable($subject->$attribute);
                        $subject->$attribute = $date->setDate((int) $examDay->format('Y'), (int) $examDay->format('m'), (int) $examDay->format('d'))->format('Y-m-d H:i:s');
                    }
                    if (!$subject->save()) throw new \RuntimeException(implode(' ', $subject->getFirstErrors()));
                }
                foreach ($round->milestones as $milestone) {
                    $day = match ($milestone->title) {
                        'ประกาศรายชื่อผู้มีสิทธิ์สอบ' => $today->modify('+25 days'),
                        'วันสอบ' => $examDay,
                        'ประกาศผลและรายงานตัว' => $examDay->modify('+5 days'),
                        'มอบตัวและชำระเงินบำรุงการศึกษา' => $examDay->modify('+7 days'),
                        default => null,
                    };
                    if ($day) { $milestone->occurs_at = $day->format('Y-m-d') . ' 08:00:00'; $milestone->save(false); }
                }
                foreach ($round->programs as $program) { $program->academic_year = $year; $program->save(false); }
            }
        });
        $this->stdout('Activated ' . count($rounds) . " admission rounds.\n");
        return 0;
    }
}
