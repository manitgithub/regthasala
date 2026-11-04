<?php
namespace app\services;
use app\models\{Application, ExamRoom, ExamRound, ExamTicket};
use Yii;
class ExamOperationsService
{
    public function allocate(int $roundId): int
    {
        return Yii::$app->db->transaction(function ($db) use ($roundId) {
            $db->createCommand('SELECT id FROM exam_round WHERE id=:id FOR UPDATE', [':id' => $roundId])->queryScalar();
            $rooms = ExamRoom::find()->where(['round_id' => $roundId])->orderBy('id')->all();
            if (!$rooms) throw new \DomainException('เพิ่มห้องสอบและจำนวนที่นั่งก่อนจัดสอบ');
            $apps = Application::find()->where(['round_id' => $roundId, 'qualification_status' => 'approved'])->with(['payment', 'ticket'])->orderBy('id')->all();
            $pending = array_values(array_filter($apps, fn($a) => $a->isEligible() && !$a->ticket?->exam_number));
            $available = [];
            foreach ($rooms as $room) {
                $used = ExamTicket::find()->select('seat')->where(['or', ['room_id' => $room->id], ['room_id' => null, 'round_id' => $roundId, 'venue' => $room->venue, 'room' => $room->name]])->andWhere(['not in', 'application_id', array_column($pending, 'id')])->column();
                for ($seat = 1; $seat <= $room->capacity; $seat++) if (!in_array((string) $seat, $used, true)) $available[] = [$room, $seat];
            }
            if (count($available) < count($pending)) throw new \DomainException('จำนวนที่นั่งไม่เพียงพอ ยังไม่มีการจัดที่นั่งใดเพิ่ม');
            foreach ($pending as $i => $app) {
                [$room, $seat] = $available[$i];
                $ticket = $app->ticket ?? new ExamTicket();
                $ticket->setAttributes(['application_id' => $app->id, 'round_id' => $roundId, 'room_id' => $room->id, 'exam_number' => (new ExamNumberService())->issue($roundId), 'venue' => $room->venue, 'room' => $room->name, 'seat' => (string) $seat, 'published' => false, 'updated_at' => date('Y-m-d H:i:s')], false);
                if (!$ticket->save()) throw new \DomainException('ไม่สามารถจัดเลขสอบได้: ' . implode(' ', $ticket->getFirstErrors()));
            }
            AuditService::record('exam.allocate', 'exam_round', $roundId, ['count' => count($pending)]);
            return count($pending);
        });
    }
    public function publish(int $roundId, bool $publish): int
    {
        return Yii::$app->db->transaction(function ($db) use ($roundId, $publish) {
            $db->createCommand('SELECT id FROM exam_round WHERE id=:id FOR UPDATE', [':id' => $roundId])->queryScalar();
            $apps = Application::find()->where(['round_id' => $roundId])->with(['ticket', 'payment'])->all();
            $count = 0;
            foreach ($apps as $app) {
                if (!$app->ticket) continue;
                if ($publish && !$app->isEligible()) continue;
                $app->ticket->published = $publish;
                if (!$app->ticket->validate()) throw new \DomainException('ข้อมูลบัตรสอบไม่ครบ กรุณาตรวจสอบเลขสอบและห้องสอบ');
                $app->ticket->save(false); $count++;
            }
            AuditService::record($publish ? 'exam.publish' : 'exam.unpublish', 'exam_round', $roundId, ['count' => $count]);
            return $count;
        });
    }
}
