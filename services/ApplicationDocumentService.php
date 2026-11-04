<?php
namespace app\services;
use app\models\AdmissionForm;

class ApplicationDocumentService
{
    private array $created = [];
    public function store(int $applicationId, AdmissionForm $form): void
    {
        $directory = \Yii::getAlias('@app/runtime/application-documents');
        \yii\helpers\FileHelper::createDirectory($directory, 0700);
        foreach (AdmissionForm::APPLICATION_DOCUMENTS as $kind => $label) {
            $file = $form->$kind;
            if (!$file) continue;
            $name = bin2hex(random_bytes(24)) . '.' . strtolower($file->extension);
            $path = $directory . '/' . $name;
            if (!$file->saveAs($path)) throw new \RuntimeException('Cannot store application document.');
            $this->created[] = $path;
            chmod($path, 0600);
            \Yii::$app->db->createCommand()->insert('application_document', [
                'application_id' => $applicationId, 'kind' => $kind, 'stored_name' => $name,
                'original_name' => mb_substr(basename($file->name), 0, 255),
                'mime_type' => \yii\helpers\FileHelper::getMimeType($path), 'size' => filesize($path),
            ])->execute();
        }
    }
    public function rollbackFiles(): void { foreach ($this->created as $path) if (is_file($path)) unlink($path); }
    public function storePayment(\app\models\Application $application, \app\models\PaymentForm $form, \app\models\ExamRound $round): void
    {
        if (!$form->validate()) throw new \DomainException('กรุณากรอกข้อมูลการโอนเงินและแนบสลิปให้ครบก่อนยืนยันใบสมัคร');
        if (!$round->hasPaymentAccount()) throw new \DomainException('รอบสอบนี้ยังไม่เปิดรับหลักฐานชำระเงิน');
        if ((int) round((float) $form->amount * 100) !== (int) round((float) $application->total * 100)) throw new \DomainException('ยอดเงินที่โอนต้องตรงกับค่าสมัคร ' . $application->total . ' บาท');
        $directory = \Yii::getAlias('@app/runtime/application-documents');
        \yii\helpers\FileHelper::createDirectory($directory, 0700);
        $file = $form->slip;
        $name = bin2hex(random_bytes(24)) . '.' . strtolower($file->extension);
        $path = $directory . '/' . $name;
        if (!$file->saveAs($path)) throw new \RuntimeException('Cannot store payment slip.');
        $this->created[] = $path;
        chmod($path, 0600);
        $db = \Yii::$app->db;
        $db->createCommand()->insert('application_document', ['application_id' => $application->id, 'kind' => 'payment_slip', 'stored_name' => $name, 'original_name' => mb_substr(basename($file->name), 0, 255), 'mime_type' => \yii\helpers\FileHelper::getMimeType($path), 'size' => filesize($path)])->execute();
        $documentId = $db->getLastInsertID();
        $paymentData = [
            'application_id' => $application->id, 'document_id' => $documentId, 'amount' => $form->amount,
            'transferred_at' => $form->transferred_at, 'transfer_reference' => $form->transfer_reference,
            'status' => 'pending_review', 'created_at' => date('Y-m-d H:i:s'),
            'account_snapshot' => new \yii\db\JsonExpression(['bank' => $round->payment_bank, 'account' => $round->payment_account, 'name' => $round->payment_account_name]),
        ];
        if ($application->payment) {
            if ($application->payment->status !== 'rejected') throw new \DomainException('ส่งสลิปใหม่ได้เฉพาะรายการที่ถูกส่งกลับแก้ไข');
            $db->createCommand()->update('application_document', ['is_current' => 0], ['id' => $application->payment->document_id])->execute();
            $db->createCommand()->update('application_payment', $paymentData + ['review_reason' => null, 'reviewed_by' => null, 'reviewed_at' => null], ['application_id' => $application->id])->execute();
        } else $db->createCommand()->insert('application_payment', $paymentData)->execute();
        $application->updateAttributes(['status' => 'payment_review']);
    }
}
