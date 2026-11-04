<?php
namespace app\controllers;

use app\models\{ExamRound, User, ApplicantIdentityForm};
use app\services\{ApplicantAccountService, IdentityAttemptLimiter};
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;

class SiteController extends Controller
{
    public function behaviors() { return ['verbs' => ['class' => VerbFilter::class, 'actions' => ['logout' => ['post']]]]; }
    public function actions() { return ['error' => ['class' => \yii\web\ErrorAction::class]]; }
    public function actionIndex()
    {
        return $this->render('index', ['rounds' => ExamRound::find()->where(['status' => 'published'])->with(['type', 'subjects', 'programs'])->orderBy('opens_at')->all()]);
    }
    public function actionAdmissionPreview($id)
    {
        $round = ExamRound::findOne(['id' => (int) $id, 'registration_mode' => 'admission']);
        if (!$round) throw new \yii\web\NotFoundHttpException();
        if (!Yii::$app->request->isGet) throw new \yii\web\MethodNotAllowedHttpException();
        if ($round->status === 'published') return $this->redirect(['/applicant/apply', 'id' => $round->id], 303);
        if (!$round->is_example) throw new \yii\web\NotFoundHttpException();
        return $this->render('../applicant/apply', ['round' => $round, 'error' => null,
            'identity' => new ApplicantIdentityForm(['scenario' => 'register']), 'admission' => new \app\models\AdmissionForm(['program_id' => $round->programs[0]->id ?? null]), 'preview' => true]);
    }
    public function actionLogin()
    {
        return $this->applicantAuth('status');
    }
    public function actionStatus() { return $this->applicantAuth('status'); }
    public function actionExamCard() { return $this->applicantAuth('ticket'); }
    public function actionResults() { return $this->applicantAuth('results'); }
    public function actionStaffLogin()
    {
        if (!Yii::$app->user->isGuest) return $this->redirect(Yii::$app->user->identity->isStaff() ? ['/admin/index'] : ['/applicant/index']);
        $error = null;
        if (Yii::$app->request->isPost) {
            $key = 'login:' . hash('sha256', Yii::$app->request->userIP);
            $attempts = (int) Yii::$app->cache->get($key);
            if ($attempts >= 10) $error = 'เข้าสู่ระบบไม่สำเร็จหลายครั้ง กรุณาลองใหม่ใน 15 นาที';
            else {
                $user = User::find()->where(['email' => strtolower(trim((string) Yii::$app->request->post('email'))), 'active' => 1, 'role' => array_keys(User::STAFF_ROLES)])->one();
                $password = (string) Yii::$app->request->post('password');
                $hash = $user ? $user->password_hash : '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
                if (Yii::$app->security->validatePassword($password, $hash) && $user) {
                    Yii::$app->cache->delete($key);
                    Yii::$app->user->login($user);
                    \app\services\AuditService::record('auth.staff_login', 'user', $user->id);
                    return $this->redirect(['/admin/index']);
                }
                Yii::$app->cache->set($key, $attempts + 1, 900);
                \app\services\AuditService::record('auth.failed', 'session', 'staff', ['channel' => 'staff']);
                $error = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
            }
        }
        return $this->render('staff-login', ['error' => $error]);
    }
    public function actionRegister()
    {
        return $this->redirect(['index'], 303);
    }
    private function applicantAuth(string $purpose)
    {
        $destination = match ($purpose) { 'ticket' => ['/applicant/tickets'], 'results' => ['/applicant/results'], default => ['/applicant/index'] };
        if (!Yii::$app->user->isGuest) return $this->redirect(Yii::$app->user->identity->isStaff() ? ['/admin/index'] : $destination);
        $model = new ApplicantIdentityForm();
        $error = null;
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $limiter = new IdentityAttemptLimiter();
            try {
                $limiter->assertAllowed($model->national_id);
                $service = new ApplicantAccountService();
                $user = $service->authenticate($model);
                Yii::$app->user->login($user);
                \app\services\AuditService::record('auth.applicant_login', 'user', $user->id);
                return $this->redirect($destination);
            } catch (\DomainException $e) {
                $limiter->failure($model->national_id);
                \app\services\AuditService::record('auth.failed', 'session', 'applicant', ['channel' => 'applicant']);
                $error = $e->getMessage();
            }
        }
        $model->birth_date = '';
        return $this->render('auth', ['error' => $error, 'model' => $model, 'purpose' => $purpose]);
    }
    public function actionLogout() { if (!Yii::$app->user->isGuest) \app\services\AuditService::record('auth.logout', 'user', Yii::$app->user->id); Yii::$app->user->logout(); return $this->goHome(); }
}
