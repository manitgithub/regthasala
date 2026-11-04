<?php
namespace app\controllers;

use app\models\{Application, ExamRound, ApplicantIdentityForm, AdmissionForm, ApplicationDocument};
use app\services\{RegistrationService, ApplicantAccountService, IdentityAttemptLimiter, ApplicationDocumentService};
use Yii;
use yii\filters\AccessControl;
use yii\web\{Controller, NotFoundHttpException};

class ApplicantController extends Controller
{
    public function behaviors() { return ['access' => ['class' => AccessControl::class,
        'denyCallback' => function () { if (Yii::$app->user->isGuest) return $this->redirect(['/site/' . (in_array($this->action->id, ['tickets', 'ticket', 'ticket-photo']) ? 'exam-card' : ($this->action->id === 'results' ? 'results' : 'status'))]); throw new \yii\web\ForbiddenHttpException(); },
        'rules' => [['allow' => true, 'actions' => ['apply']], ['allow' => true, 'roles' => ['@']]]]]; }
    public function actionIndex()
    {
        return $this->render('index', ['applications' => Application::find()->where(['user_id' => Yii::$app->user->id])->with(['round', 'items.subject', 'payment', 'ticket'])->orderBy(['id' => SORT_DESC])->all()]);
    }
    public function actionTickets()
    {
        Yii::$app->response->headers->set('Cache-Control', 'no-store');
        return $this->render('tickets', ['applications' => Application::find()->where(['user_id' => Yii::$app->user->id])->with(['round', 'ticket'])->orderBy(['id' => SORT_DESC])->all()]);
    }
    public function actionResults()
    {
        Yii::$app->response->headers->set('Cache-Control', 'no-store');
        return $this->render('results', ['applications' => Application::find()->where(['user_id' => Yii::$app->user->id])->with(['round', 'result', 'items.subject', 'items.score', 'payment'])->orderBy(['id' => SORT_DESC])->all()]);
    }
    public function actionCorrection($id)
    {
        $app = Application::findOne(['id' => (int) $id, 'user_id' => Yii::$app->user->id]);
        if (!$app || $app->status !== 'correction_required' || !$app->profile) throw new NotFoundHttpException('ไม่พบรายการที่เปิดให้แก้ไข');
        $admission = new AdmissionForm(['scenario' => 'correction']);
        $admission->setAttributes($app->profile->data); $admission->program_id = $app->program_id;
        $payment = new \app\models\PaymentForm(); $error = null;
        if (Yii::$app->request->isPost) {
            $admission->load(Yii::$app->request->post()); $admission->program_id = $app->program_id;
            $admission->photo = \yii\web\UploadedFile::getInstance($admission, 'photo');
            $payment->load(Yii::$app->request->post()); $payment->slip = \yii\web\UploadedFile::getInstance($payment, 'slip');
            if ($payment->transferred_at) $payment->transferred_at = str_replace('T', ' ', $payment->transferred_at) . (strlen($payment->transferred_at) === 16 ? ':00' : '');
            $files = new ApplicationDocumentService(); $tx = Yii::$app->db->beginTransaction();
            try {
                Yii::$app->db->createCommand('SELECT id FROM exam_round WHERE id=:id FOR UPDATE', [':id' => $app->round_id])->queryScalar();
                Yii::$app->db->createCommand('SELECT id FROM application WHERE id=:id FOR UPDATE', [':id' => $app->id])->queryScalar();
                $app->refresh();
                if ($app->status !== 'correction_required' || $app->round->results_published_at || $app->ticket?->published) throw new \DomainException('ใบสมัครนี้ยังไม่เปิดให้แก้ไข');
                if (!$admission->validate()) throw new \DomainException('กรุณาตรวจสอบข้อมูลที่แก้ไข');
                if ($app->admission_snapshot && !$admission->checkProgram(new \app\models\AdmissionProgram($app->admission_snapshot))) throw new \DomainException('ผลการเรียนไม่ตรงตามเกณฑ์โครงการ');
                if (!$admission->photo && $app->getCurrentDocuments()->andWhere(['kind' => 'photo', 'review_status' => 'rejected'])->exists()) throw new \DomainException('กรุณาแนบรูปถ่ายใหม่ตามที่เจ้าหน้าที่แจ้ง');
                if ($admission->photo) Yii::$app->db->createCommand()->update('application_document', ['is_current' => 0], ['application_id' => $app->id, 'kind' => 'photo'])->execute();
                $files->store($app->id, $admission);
                if ($app->payment?->status === 'rejected') $files->storePayment($app, $payment, $app->round);
                $oldProfile = $app->profile->data; $newProfile = $admission->getProfileData();
                $changedFields = array_keys(array_filter($newProfile, fn($value, $key) => (string) $value !== (string) ($oldProfile[$key] ?? ''), ARRAY_FILTER_USE_BOTH));
                Yii::$app->db->createCommand()->update('application_profile', ['data' => new \yii\db\JsonExpression($newProfile)], ['application_id' => $app->id])->execute();
                $app->updateAttributes(['qualification_status' => 'pending', 'reviewed_by' => null, 'reviewed_at' => null]); $app->refresh(); \app\services\ReviewService::syncStatus($app);
                \app\services\AuditService::record('application.resubmit', 'application', $app->id, ['changed_fields' => $changedFields, 'new_photo' => (bool) $admission->photo, 'new_slip' => (bool) $payment->slip]);
                $tx->commit(); Yii::$app->session->setFlash('success', 'ส่งข้อมูลแก้ไขแล้ว รอเจ้าหน้าที่ตรวจสอบอีกครั้ง'); return $this->redirect(['view', 'id' => $app->id]);
            } catch (\DomainException $e) { $files->rollbackFiles(); if ($tx->isActive) $tx->rollBack(); $error = $e->getMessage(); }
            catch (\Throwable $e) { $files->rollbackFiles(); if ($tx->isActive) $tx->rollBack(); throw $e; }
        }
        return $this->render('correction', ['application' => $app, 'admission' => $admission, 'payment' => $payment, 'error' => $error]);
    }
    private function publishedTicketApplication($id): Application
    {
        $application = Application::find()->where(['id' => (int) $id])->with(['round', 'applicant', 'profile', 'items.subject', 'ticket'])->one();
        if (!$application || (!Yii::$app->user->identity->can('exam.manage') && (int) $application->user_id !== (int) Yii::$app->user->id) || !$application->ticket || !$application->ticket->published || !$application->isEligible()) throw new NotFoundHttpException('ยังไม่เปิดพิมพ์บัตรประจำตัวสอบ');
        Yii::$app->response->headers->set('Cache-Control', 'no-store');
        return $application;
    }
    public function actionTicket($id)
    {
        return $this->render('ticket', ['application' => $this->publishedTicketApplication($id)]);
    }
    public function actionTicketPhoto($id)
    {
        $application = $this->publishedTicketApplication($id);
        $photo = $application->getCurrentDocuments()->andWhere(['kind' => 'photo'])->one();
        if (!$photo || !in_array($photo->mime_type, ['image/jpeg', 'image/png'], true)) throw new NotFoundHttpException();
        return Yii::$app->response->sendFile(Yii::getAlias('@app/runtime/application-documents/') . $photo->stored_name, 'photo.' . ($photo->mime_type === 'image/png' ? 'png' : 'jpg'), ['mimeType' => $photo->mime_type, 'inline' => true]);
    }
    public function actionView($id)
    {
        $application = Application::find()->where(['id' => (int) $id])->with(['round', 'applicant', 'items.subject', 'profile', 'documents'])->one();
        if (!$application || (!Yii::$app->user->identity->can('applications.view') && (int) $application->user_id !== (int) Yii::$app->user->id)) throw new NotFoundHttpException();
        return $this->render('view', compact('application'));
    }
    public function actionApply($id)
    {
        $round = ExamRound::find()->where(['id' => (int) $id, 'status' => 'published'])->with(['subjects', 'milestones'])->one();
        if (!$round) throw new NotFoundHttpException();
        $error = null;
        $identity = new ApplicantIdentityForm(['scenario' => 'register']);
        $admission = $round->registration_mode === 'admission' ? new AdmissionForm() : null;
        $guest = Yii::$app->user->isGuest;
        $payment = new \app\models\PaymentForm();
        if ($admission && !$guest) $admission->applicant_name = Yii::$app->user->identity->name;
        if (Yii::$app->request->isPost) {
            $transaction = null;
            $documents = new ApplicationDocumentService();
            try {
                if (!$round->hasPaymentAccount()) throw new \DomainException('รอบสอบนี้ยังไม่พร้อมรับสมัคร กรุณารอโรงเรียนกำหนดบัญชีรับชำระเงิน');
                $payment->load(Yii::$app->request->post());
                $payment->slip = \yii\web\UploadedFile::getInstance($payment, 'slip');
                if ($payment->transferred_at) $payment->transferred_at = str_replace('T', ' ', $payment->transferred_at) . (strlen($payment->transferred_at) === 16 ? ':00' : '');
                if ($admission) {
                    $admission->load(Yii::$app->request->post());
                    foreach (AdmissionForm::APPLICATION_DOCUMENTS as $kind => $label) $admission->$kind = \yii\web\UploadedFile::getInstance($admission, $kind);
                }
                if ($guest) $identity->load(Yii::$app->request->post());
                if (!$payment->validate()) throw new \DomainException('กรุณาตรวจสอบข้อมูลการชำระเงิน');
                $selection = Yii::$app->request->post('subjects', []);
                if (!is_array($selection)) throw new \DomainException('กรุณาเลือกรายวิชาที่ต้องการสมัคร');
                $transaction = Yii::$app->db->beginTransaction();
                if ($guest) {
                    $loaded = $identity->load(Yii::$app->request->post());
                    if ($admission) $identity->name = $admission->applicant_name;
                    if (!$loaded || !$identity->validate()) throw new \DomainException('กรุณาตรวจสอบข้อมูลผู้สมัคร');
                    $limiter = new IdentityAttemptLimiter();
                    $limiter->assertAllowed($identity->national_id);
                    try { $user = (new ApplicantAccountService())->registerOrAuthenticate($identity); }
                    catch (\DomainException $e) { $limiter->failure($identity->national_id); throw $e; }
                } else $user = Yii::$app->user->identity;
                $application = (new RegistrationService())->register((int) $user->id, (int) $round->id, $selection, $admission);
                if ($admission) $documents->store((int) $application->id, $admission);
                $round->refresh();
                $documents->storePayment($application, $payment, $round);
                \app\services\AuditService::record('application.submit', 'application', $application->id, ['status' => $application->status], $user);
                if ($transaction) $transaction->commit();
                if ($guest) Yii::$app->user->login($user);
                Yii::$app->session->setFlash('success', 'ยืนยันใบสมัครและส่งสลิปแล้ว รอเจ้าหน้าที่ตรวจสอบการชำระเงิน');
                return $this->redirect(['index']);
            } catch (\DomainException $e) {
                $documents->rollbackFiles();
                if ($transaction && $transaction->isActive) $transaction->rollBack();
                $error = $e->getMessage();
            } catch (\Throwable $e) {
                $documents->rollbackFiles();
                if ($transaction && $transaction->isActive) $transaction->rollBack();
                throw $e;
            }
        }
        $identity->birth_date = '';
        return $this->render('apply', ['round' => $round, 'error' => $error, 'identity' => $identity, 'admission' => $admission, 'payment' => $payment, 'preview' => false]);
    }
    public function actionDocument($id)
    {
        $document = ApplicationDocument::findOne((int) $id);
        if (!$document || (!Yii::$app->user->identity->canViewDocument($document->kind) && (int) $document->application->user_id !== (int) Yii::$app->user->id)) throw new NotFoundHttpException();
        \app\services\AuditService::record('document.download', 'application_document', $document->id, ['application_id' => $document->application_id, 'kind' => $document->kind]);
        return Yii::$app->response->sendFile(Yii::getAlias('@app/runtime/application-documents/') . $document->stored_name, $document->original_name, ['mimeType' => $document->mime_type, 'inline' => false]);
    }
}
