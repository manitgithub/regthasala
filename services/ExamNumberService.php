<?php
namespace app\services;

use app\models\{ExamRound, ExamTicket, ExamNumberSettingsForm};
use Yii;

class ExamNumberService
{
    public static function prefix(ExamRound $round): string { return $round->exam_number_prefix ?? 'R' . $round->id . '-'; }

    public function configure(int $roundId, ExamNumberSettingsForm $form): void
    {
        if (!$form->validate()) throw new \DomainException(implode(' ', $form->getFirstErrors()));
        Yii::$app->db->transaction(function ($db) use ($roundId, $form) {
            $db->createCommand('SELECT id FROM exam_round WHERE id=:id FOR UPDATE', [':id' => $roundId])->queryScalar();
            $round = ExamRound::findOne($roundId);
            if (!$round) throw new \DomainException('ไม่พบรอบสอบ');
            if (ExamTicket::find()->where(['round_id' => $roundId])->andWhere(['not', ['exam_number' => null]])->exists()) throw new \DomainException('รอบนี้ออกเลขประจำตัวสอบแล้ว ไม่สามารถเปลี่ยนรูปแบบหรือเลขเริ่มต้นได้');
            $before = $round->getAttributes(['exam_number_prefix', 'exam_number_digits', 'exam_number_start']);
            $round->exam_number_prefix = $form->prefix;
            $round->exam_number_digits = (int) $form->digits;
            $round->exam_number_start = $round->exam_number_next = (int) $form->start;
            $round->save(false, ['exam_number_prefix', 'exam_number_digits', 'exam_number_start', 'exam_number_next']);
            AuditService::record('exam.number_settings', 'exam_round', $roundId, ['before' => $before, 'after' => $round->getAttributes(array_keys($before))]);
        });
    }

    // Called inside the ticket-saving transaction so failed saves do not consume a number.
    public function issue(int $roundId): string
    {
        $db = Yii::$app->db;
        if (!$db->getTransaction()?->isActive) throw new \LogicException('Exam numbering requires a transaction.');
        $db->createCommand('SELECT id FROM exam_round WHERE id=:id FOR UPDATE', [':id' => $roundId])->queryScalar();
        $round = ExamRound::findOne($roundId);
        if (!$round) throw new \DomainException('ไม่พบรอบสอบ');
        $next = (int) $round->exam_number_next;
        do {
            if (strlen((string) $next) > (int) $round->exam_number_digits) throw new \DomainException('เลขประจำตัวสอบเต็มจำนวนหลักที่ตั้งไว้ กรุณาติดต่อผู้ดูแลระบบ');
            $number = self::prefix($round) . str_pad((string) $next++, (int) $round->exam_number_digits, '0', STR_PAD_LEFT);
        } while (ExamTicket::find()->where(['round_id' => $roundId, 'exam_number' => $number])->exists());
        $round->exam_number_next = $next;
        $round->save(false, ['exam_number_next']);
        return $number;
    }
}
