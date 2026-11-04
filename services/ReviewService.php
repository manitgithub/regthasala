<?php
namespace app\services;
use app\models\{Application, ApplicationDocument};
use Yii;
class ReviewService
{
    public static function revision(Application $app): string
    {
        $docs = $app->getCurrentDocuments()->select(['id', 'review_status', 'review_reason', 'reviewed_at'])->orderBy('id')->asArray()->all();
        return hash('sha256', json_encode([$app->qualification_status, $app->qualification_reason, $app->reviewed_at, $app->payment?->attributes, $docs]));
    }
    public function decide(int $id, string $kind, string $decision, string $reason, string $revision, ?int $documentId = null): void
    {
        Yii::$app->db->transaction(function ($db) use ($id, $kind, $decision, $reason, $revision, $documentId) {
            $initial = Application::findOne($id);
            if (!$initial) throw new \DomainException('ไม่พบใบสมัคร');
            $db->createCommand('SELECT id FROM exam_round WHERE id=:id FOR UPDATE', [':id' => $initial->round_id])->queryScalar();
            $db->createCommand('SELECT id FROM application WHERE id=:id FOR UPDATE', [':id' => $id])->queryScalar();
            $app = Application::findOne($id);
            if (!$app) throw new \DomainException('ไม่พบใบสมัคร');
            if (!hash_equals(self::revision($app), $revision)) throw new \DomainException('ข้อมูลเปลี่ยนแปลงแล้ว กรุณาโหลดหน้าใหม่ก่อนพิจารณา');
            if ($app->round->results_published_at) throw new \DomainException('ต้องถอนประกาศผลก่อนเปลี่ยนการพิจารณา');
            if ($app->ticket?->published) throw new \DomainException('ต้องปิดพิมพ์บัตรสอบก่อนเปลี่ยนการพิจารณา');
            $reason = trim($reason);
            if (mb_strlen($reason) > 2000) throw new \DomainException('หมายเหตุต้องไม่เกิน 2,000 ตัวอักษร');
            $meta = ['reviewed_by' => Yii::$app->user->id, 'reviewed_at' => date('Y-m-d H:i:s')];
            if ($kind === 'qualification') {
                if (!in_array($decision, ['approved', 'correction', 'rejected'], true)) throw new \DomainException('ผลพิจารณาไม่ถูกต้อง');
                if ($decision !== 'approved' && $reason === '') throw new \DomainException('กรุณาระบุเหตุผลการส่งกลับแก้ไขหรือไม่อนุมัติ');
                if ($decision === 'approved' && $app->getCurrentDocuments()->andWhere(['<>', 'kind', 'payment_slip'])->andWhere(['<>', 'review_status', 'accepted'])->exists()) throw new \DomainException('ตรวจเอกสารประกอบให้ผ่านก่อนอนุมัติคุณสมบัติ');
                $before = $app->qualification_status;
                $app->updateAttributes($meta + ['qualification_status' => $decision, 'qualification_reason' => $reason]);
            } elseif ($kind === 'payment') {
                if (!in_array($decision, ['verified', 'rejected'], true) || !$app->payment) throw new \DomainException('ไม่มีสลิปให้ตรวจหรือผลพิจารณาไม่ถูกต้อง');
                if ($decision === 'rejected' && $reason === '') throw new \DomainException('กรุณาระบุเหตุผลที่ไม่รับหลักฐานชำระเงิน');
                $before = $app->payment->status;
                $app->payment->updateAttributes($meta + ['status' => $decision, 'review_reason' => $reason]);
                ApplicationDocument::updateAll($meta + ['review_status' => $decision === 'verified' ? 'accepted' : 'rejected', 'review_reason' => $reason], ['id' => $app->payment->document_id]);
                $db->createCommand()->update('application_subject', ['payment_status' => $decision === 'verified' ? 'paid' : 'unpaid'], ['application_id' => $id])->execute();
            } elseif ($kind === 'document') {
                $document = ApplicationDocument::findOne(['id' => $documentId, 'application_id' => $id, 'is_current' => 1]);
                if (!$document || $document->kind === 'payment_slip' || !in_array($decision, ['accepted', 'rejected'], true)) throw new \DomainException('เอกสารไม่ถูกต้อง');
                if ($decision === 'rejected' && $reason === '') throw new \DomainException('กรุณาระบุสาเหตุที่ให้แก้ไขเอกสาร');
                $before = $document->review_status;
                $document->updateAttributes($meta + ['review_status' => $decision, 'review_reason' => $reason]);
                if ($decision === 'rejected') $app->updateAttributes(['qualification_status' => 'correction', 'qualification_reason' => $reason]);
            } else throw new \DomainException('รายการพิจารณาไม่ถูกต้อง');
            $app->refresh();
            self::syncStatus($app);
            AuditService::record('review.' . $kind, 'application', $id, ['before' => $before, 'after' => $decision, 'reason' => $reason, 'document_id' => $documentId]);
        });
    }
    public static function syncStatus(Application $app): void
    {
        $status = !$app->payment ? 'incomplete' : ($app->qualification_status === 'rejected' ? 'rejected' : ($app->qualification_status === 'correction' || $app->payment->status === 'rejected' ? 'correction_required' : ($app->isEligible() ? 'eligible' : ($app->payment->status === 'verified' ? 'qualification_review' : 'payment_review'))));
        $app->updateAttributes(['status' => $status]);
    }
}
