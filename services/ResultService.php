<?php
namespace app\services;
use app\models\{Application, ApplicationSubject, ApplicationResult, ExamRound, ExamScore};
use Yii;
class ResultService
{
    public static function revision(int $roundId): string
    {
        $scores = (new \yii\db\Query())->from('application_subject i')->innerJoin('application a', 'a.id=i.application_id')->leftJoin('exam_score s', 's.item_id=i.id')->select(['i.id', 's.score', 's.attendance', 's.updated_at'])->where(['a.round_id' => $roundId])->orderBy('i.id')->all();
        $outcomes = (new \yii\db\Query())->from('application_result x')->innerJoin('application a', 'a.id=x.application_id')->select(['x.application_id', 'x.outcome', 'x.note', 'x.updated_at'])->where(['a.round_id' => $roundId])->orderBy('x.application_id')->all();
        return hash('sha256', json_encode([$scores, $outcomes, ExamRound::findOne($roundId)?->results_published_at]));
    }
    public function saveScores(int $roundId, array $rows, ?string $revision = null): void
    {
        Yii::$app->db->transaction(function ($db) use ($roundId, $rows, $revision) {
            $this->lockRound($roundId, $revision);
            if (count($rows) > 500) throw new \DomainException('บันทึกได้ครั้งละไม่เกิน 500 รายการ');
            $changes = [];
            foreach ($rows as $id => $row) {
                $item = ApplicationSubject::findOne((int) $id);
                if (!$item || (int) $item->application->round_id !== $roundId || !$item->application->isEligible() || !is_array($row)) throw new \DomainException('รายชื่อผู้เข้าสอบไม่ถูกต้อง');
                $score = $item->score ?? new ExamScore(['item_id' => $item->id]);
                $before = $score->getAttributes(['score', 'attendance']);
                $score->attendance = $row['attendance'] ?? 'pending';
                $score->score = $row['score'] ?? null;
                if (!$score->validate() || $score->score !== null && (float) $score->score > (float) $item->subject->max_score) throw new \DomainException('คะแนน / การเข้าสอบไม่ถูกต้อง: ' . $item->application->reference . ' (คะแนนเต็ม ' . $item->subject->max_score . ')');
                $score->updated_by = Yii::$app->user->id; $score->updated_at = date('Y-m-d H:i:s'); $score->save(false);
                $changes[$id] = ['before' => $before, 'after' => $score->getAttributes(['score', 'attendance'])];
            }
            AuditService::record('results.scores', 'exam_round', $roundId, ['changes' => $changes]);
        });
    }
    public function saveOutcomes(int $roundId, array $rows, ?string $revision = null): void
    {
        Yii::$app->db->transaction(function ($db) use ($roundId, $rows, $revision) {
            $this->lockRound($roundId, $revision);
            foreach ($rows as $id => $row) {
                $app = Application::findOne((int) $id);
                if (!$app || (int) $app->round_id !== $roundId || !$app->isEligible() || !is_array($row) || !isset(ApplicationResult::OUTCOMES[$row['outcome'] ?? ''])) throw new \DomainException('ผลคัดเลือกไม่ถูกต้อง');
                $note = trim((string) ($row['note'] ?? ''));
                if (mb_strlen($note) > 2000) throw new \DomainException('หมายเหตุยาวเกินกำหนด');
                $result = $app->result ?? new ApplicationResult(['application_id' => $id]);
                $before = $result->outcome; $result->outcome = $row['outcome']; $result->note = $note; $result->updated_by = Yii::$app->user->id; $result->updated_at = date('Y-m-d H:i:s'); $result->save(false);
                AuditService::record('results.outcome', 'application', $id, ['before' => $before, 'after' => $result->outcome, 'note' => $note]);
            }
        });
    }
    private function lockRound(int $id, ?string $revision = null): void
    {
        $row = Yii::$app->db->createCommand('SELECT * FROM exam_round WHERE id=:id FOR UPDATE', [':id' => $id])->queryOne();
        if (!$row || $row['results_published_at']) throw new \DomainException('ต้องถอนประกาศผลก่อนแก้ไขคะแนนหรือผลคัดเลือก');
        if ($revision !== null && !hash_equals(self::revision($id), $revision)) throw new \DomainException('คะแนนหรือผลคัดเลือกเปลี่ยนแปลงแล้ว กรุณาโหลดหน้าใหม่');
    }
    public function publish(int $roundId, bool $publish, ?string $revision = null): void
    {
        Yii::$app->db->transaction(function ($db) use ($roundId, $publish, $revision) {
            $db->createCommand('SELECT id FROM exam_round WHERE id=:id FOR UPDATE', [':id' => $roundId])->queryScalar();
            $round = ExamRound::findOne($roundId);
            if (!$round) throw new \DomainException('ไม่พบรอบสอบ');
            if ($revision !== null && !hash_equals(self::revision($roundId), $revision)) throw new \DomainException('คะแนนหรือผลคัดเลือกเปลี่ยนแปลงแล้ว กรุณาโหลดหน้าใหม่');
            if ($publish) {
                $apps = Application::find()->where(['round_id' => $roundId, 'qualification_status' => 'approved'])->with(['payment', 'result', 'items.score', 'items.subject'])->all();
                $count = 0;
                foreach ($apps as $app) {
                    if (!$app->isEligible()) continue;
                    $count++;
                    if (!$app->result || !$app->items) throw new \DomainException('ยังระบุผลคัดเลือกไม่ครบ: ' . $app->reference);
                    foreach ($app->items as $item) if (!$item->score || $item->score->attendance === 'pending' || $item->score->attendance === 'present' && ($item->score->score === null || $item->score->score > $item->subject->max_score)) throw new \DomainException('คะแนน / สถานะเข้าสอบยังไม่ครบ: ' . $app->reference);
                }
                if (!$count) throw new \DomainException('ไม่มีผู้มีสิทธิ์สอบให้ประกาศผล');
            }
            $round->updateAttributes(['results_published_at' => $publish ? date('Y-m-d H:i:s') : null]);
            AuditService::record($publish ? 'results.publish' : 'results.unpublish', 'exam_round', $roundId);
        });
    }
}
