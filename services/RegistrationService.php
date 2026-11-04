<?php
namespace app\services;

use app\models\{Application, ExamRound, Subject, AdmissionProgram, AdmissionForm};
use yii\db\Query;

class RegistrationService
{
    public function register(int $userId, int $roundId, array $subjectIds, ?AdmissionForm $admission = null): Application
    {
        $ids = array_unique(array_map('intval', $subjectIds));
        sort($ids);
        if (!$ids && !$admission) throw new \DomainException('กรุณาเลือกอย่างน้อย 1 วิชา');
        return \Yii::$app->db->transaction(function ($db) use ($userId, $roundId, $ids, $admission) {
            // Serialize registration with round edits and other applicants before checking seat limits.
            $row = $db->createCommand('SELECT * FROM exam_round WHERE id=:id FOR UPDATE', [':id' => $roundId])->queryOne();
            if (!$row || !ExamRound::findOne($roundId)->isOpen()) throw new \DomainException('รอบสอบนี้ยังไม่เปิดรับสมัครหรือปิดรับสมัครแล้ว');
            if (Application::find()->where(['user_id' => $userId, 'round_id' => $roundId])->exists()) throw new \DomainException('คุณสมัครรอบสอบนี้แล้ว');
            $program = null;
            if ($row['registration_mode'] === 'admission') {
                if (!$admission || !$admission->validate()) throw new \DomainException('กรุณากรอกข้อมูลและแนบเอกสารให้ครบถ้วน');
                $program = AdmissionProgram::findOne(['id' => $admission->program_id, 'round_id' => $roundId, 'status' => 'open']);
                if (!$program) throw new \DomainException('โครงการที่เลือกไม่เปิดรับสมัคร');
                if (!$admission->checkProgram($program)) throw new \DomainException('คุณสมบัติไม่ตรงตามเกณฑ์โครงการ กรุณาตรวจสอบข้อมูลการศึกษา');
                $ids = array_map(fn($subject) => (int) $subject->id, $program->subjects);
                if (!$ids) throw new \DomainException('โครงการนี้ยังไม่ได้กำหนดชุดวิชาสอบ');
            }
            if (!$ids) throw new \DomainException('กรุณาเลือกอย่างน้อย 1 วิชา');
            $subjects = Subject::find()->where(['round_id' => $roundId, 'id' => $ids, 'status' => 'open'])->orderBy('starts_at')->all();
            if (count($subjects) !== count($ids)) throw new \DomainException('รายวิชาที่เลือกไม่ถูกต้องหรือปิดรับสมัครแล้ว');
            $lastEnd = null;
            $cents = 0;
            foreach ($subjects as $subject) {
                if ($lastEnd && $subject->starts_at < $lastEnd) throw new \DomainException('รายวิชาที่เลือกมีเวลาสอบซ้อนกัน');
                if ($subject->getReservedCount() >= $subject->capacity) throw new \DomainException('วิชา ' . $subject->name . ' เต็มแล้ว');
                $lastEnd = $subject->ends_at;
                if (!$program) $cents += (int) round((float) $subject->fee * 100);
            }
            if ($program) $cents = (int) round((float) $program->application_fee * 100);
            $application = new Application([
                'reference' => 'EX-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4))),
                'user_id' => $userId, 'round_id' => $roundId, 'total' => number_format($cents / 100, 2, '.', ''),
                'status' => 'incomplete', 'created_at' => date('Y-m-d H:i:s'),
                'program_id' => $program?->id, 'application_fee' => $program ? $program->application_fee : 0,
                'admission_snapshot' => $program ? $program->getSnapshot() : null,
            ]);
            $application->save(false);
            foreach ($subjects as $subject) $db->createCommand()->insert('application_subject', [
                'application_id' => $application->id, 'subject_id' => $subject->id, 'fee' => $program ? 0 : $subject->fee, 'payment_status' => 'unpaid',
            ])->execute();
            if ($program) $db->createCommand()->insert('application_profile', ['application_id' => $application->id, 'data' => new \yii\db\JsonExpression($admission->getProfileData())])->execute();
            return $application;
        });
    }
}
