<?php
namespace app\services;
use Yii;
class AuditService
{
    public const LABELS = ['auth.staff_login' => 'เจ้าหน้าที่เข้าสู่ระบบ', 'auth.applicant_login' => 'ผู้สมัครยืนยันตัวตน', 'auth.logout' => 'ออกจากระบบ', 'application.submit' => 'ส่งใบสมัคร', 'application.resubmit' => 'ส่งข้อมูลแก้ไข', 'document.download' => 'ดาวน์โหลดเอกสาร', 'review.document' => 'ตรวจเอกสาร', 'review.qualification' => 'พิจารณาคุณสมบัติ', 'review.payment' => 'ตรวจรับชำระเงิน', 'exam.room_save' => 'ตั้งค่าห้องสอบ', 'exam.allocate' => 'จัดที่นั่งสอบ', 'exam.publish' => 'เปิดพิมพ์บัตรสอบ', 'exam.unpublish' => 'ปิดพิมพ์บัตรสอบ', 'exam.ticket_save' => 'แก้ไขบัตรสอบ', 'exam.roster_view' => 'เปิดรายชื่อผู้เข้าสอบ', 'results.scores' => 'บันทึกคะแนน / การเข้าสอบ', 'results.outcome' => 'บันทึกผลคัดเลือก', 'results.publish' => 'เปิดประกาศผล', 'results.unpublish' => 'ถอนประกาศผล', 'reports.export' => 'ส่งออกรายงาน', 'users.save' => 'แก้ไขบัญชี / สิทธิ์เจ้าหน้าที่', 'settings.round' => 'ตั้งค่ารอบสอบ', 'settings.subject' => 'ตั้งค่ารายวิชา', 'settings.type' => 'ตั้งค่าประเภทการสอบ', 'settings.program' => 'ตั้งค่าโครงการ', 'settings.milestone' => 'ตั้งค่ากำหนดการ'];
    public static function record(string $action, string $entity, $id, array $details = [], ?\app\models\User $actorOverride = null): void
    {
        $actor = $actorOverride ?? (Yii::$app->has('user') ? Yii::$app->user->identity : null);
        foreach (['password', 'password_hash', 'auth_key', 'national_id', 'birth_date', 'birth_date_hash', 'stored_name'] as $key) unset($details[$key]);
        Yii::$app->db->createCommand()->insert('audit_log', ['actor_id' => $actor?->id, 'actor_name' => $actor?->name ?? 'ระบบ', 'action' => $action, 'entity' => $entity, 'entity_id' => (string) $id, 'details' => new \yii\db\JsonExpression($details), 'created_at' => date('Y-m-d H:i:s')])->execute();
    }
}
