<?php
namespace app\controllers;

use app\models\{ExamType, ExamRound, Subject, Milestone, Application, AdmissionProgram, RoundScheduleForm};
use Yii;
use yii\filters\AccessControl;
use yii\web\{Controller, NotFoundHttpException};

class AdminController extends Controller
{
    public function behaviors() { return ['verbs' => ['class' => \yii\filters\VerbFilter::class, 'actions' => ['save-ticket' => ['post']]], 'access' => [
        'class' => AccessControl::class,
        'denyCallback' => function () {
            if (Yii::$app->user->isGuest) return $this->redirect(['/site/staff-login']);
            throw new \yii\web\ForbiddenHttpException('คุณไม่มีสิทธิ์เข้าถึงส่วนเจ้าหน้าที่');
        },
        'rules' => [['allow' => true, 'roles' => ['@'], 'matchCallback' => fn() => Yii::$app->user->identity->can(match ($this->action->id) { 'edit' => 'rounds.manage', 'applications' => 'applications.view', 'save-ticket' => 'exam.manage', default => 'rounds.view' })]],
    ]]; }
    public function actionIndex()
    {
        return $this->render('index', [
            'rounds' => ExamRound::find()->with(['type', 'subjects', 'programs'])->orderBy(['id' => SORT_DESC])->all(),
            'types' => ExamType::find()->orderBy('name')->all(),
            'subjectCount' => Subject::find()->count(), 'applicationCount' => Application::find()->count(),
            'reviewCount' => Application::find()->where(['status' => 'payment_review'])->count(),
            'roundApplicationCounts' => (new \yii\db\Query())->select(['round_id', 'count' => 'COUNT(*)'])->from('application')->groupBy('round_id')->indexBy('round_id')->all(),
        ]);
    }
    public function actionRound($id)
    {
        return $this->render('round', ['round' => $this->findRound($id)]);
    }
    public function actionEdit($entity, $id = null, $roundId = null)
    {
        $classes = ['round' => ExamRound::class, 'type' => ExamType::class, 'subject' => Subject::class, 'milestone' => Milestone::class, 'program' => AdmissionProgram::class];
        if (!isset($classes[$entity])) throw new NotFoundHttpException();
        $class = $classes[$entity];
        $model = $id ? $class::findOne((int) $id) : new $class();
        if (!$model) throw new NotFoundHttpException();
        $schedule = $entity === 'round' ? RoundScheduleForm::forRound($model) : null;
        if (in_array($entity, ['subject', 'milestone', 'program'])) {
            $round = $this->findRound($model->isNewRecord ? $roundId : $model->round_id);
            $model->round_id = $round->id;
        }
        if ($model->isNewRecord) {
            if ($entity === 'round') $model->status = 'draft';
            if ($entity === 'subject') { $model->status = 'open'; $model->max_score = 100; }
            if ($entity === 'program') { $model->status = 'open'; $model->academic_year = (int) date('Y') + 543; $model->minimum_gpa = 0; $model->application_fee = 0; }
        }
        if ($model->load(Yii::$app->request->post())) {
            $saveSchedule = $schedule && Yii::$app->request->post('schedule_included') === '1';
            if ($saveSchedule) $schedule->loadRows(Yii::$app->request->post('ScheduleRows', []), Yii::$app->request->post('schedule_revision', ''));
            if (isset($round)) $model->round_id = $round->id;
            foreach (['opens_at', 'closes_at', 'payment_due_at', 'starts_at', 'ends_at', 'occurs_at'] as $attribute) {
                if ($model->hasAttribute($attribute)) {
                    $value = (string) $model->$attribute;
                    if ($entity === 'milestone' && $attribute === 'ends_at' && $value === '') { $model->$attribute = null; continue; }
                    $date = \DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $value);
                    if ($date && $date->format('Y-m-d\\TH:i') === $value) $model->$attribute = $date->format('Y-m-d H:i:s');
                }
            }
            // Use the same round lock as registration so seat limits cannot change mid-registration.
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (isset($round)) Yii::$app->db->createCommand('SELECT id FROM exam_round WHERE id=:id FOR UPDATE', [':id' => $round->id])->queryScalar();
                if ($entity === 'round' && !$model->isNewRecord) Yii::$app->db->createCommand('SELECT id FROM exam_round WHERE id=:id FOR UPDATE', [':id' => $model->id])->queryScalar();
                $valid = $model->validate();
                if ($entity === 'subject' && $round->results_published_at) { $model->addError('name', 'ต้องถอนประกาศผลก่อนแก้ไขรายวิชา'); $valid = false; }
                if ($saveSchedule && !$schedule->validateForRound($model)) $valid = false;
                $programSubjects = [];
                if ($entity === 'program') {
                    $selected = Yii::$app->request->post('program_subjects', []);
                    $ids = is_array($selected) ? array_unique(array_map('intval', $selected)) : [];
                    $programSubjects = Subject::find()->where(['round_id' => $round->id, 'id' => $ids])->all();
                    if (!$ids || count($programSubjects) !== count($ids)) { $model->addError('name', 'กรุณาเลือกชุดวิชาสอบของโครงการจากรอบนี้'); $valid = false; }
                }
                if ($entity === 'round' && $model->status === 'published' && ($model->isNewRecord || !$model->getSubjects()->exists())) {
                    $model->addError('status', 'บันทึกเป็นแบบร่างและเพิ่มรายวิชาก่อนเปิดรับสมัคร'); $valid = false;
                }
                if ($entity === 'round' && $model->status === 'published' && $model->registration_mode === 'admission' && !$model->getPrograms()->where(['status' => 'open'])->exists()) {
                    $model->addError('status', 'เพิ่มโครงการรับสมัครก่อนเผยแพร่รอบรับนักเรียน'); $valid = false;
                }
                $changes = $model->getDirtyAttributes();
                $beforeChanges = array_intersect_key($model->getOldAttributes(), $changes);
                if ($valid && $model->save(false)) {
                    if ($saveSchedule) $schedule->saveForRound((int) $model->id);
                    if ($entity === 'program') {
                        Yii::$app->db->createCommand()->delete('program_subject', ['program_id' => $model->id])->execute();
                        foreach ($programSubjects as $subject) Yii::$app->db->createCommand()->insert('program_subject', ['program_id' => $model->id, 'subject_id' => $subject->id])->execute();
                    }
                    \app\services\AuditService::record('settings.' . $entity, $model::tableName(), $model->id, ['before' => $beforeChanges, 'after' => $changes]);
                    $transaction->commit();
                    Yii::$app->session->setFlash('success', 'บันทึกข้อมูลเรียบร้อยแล้ว');
                    return $this->redirect($entity === 'type' ? ['index'] : ['round', 'id' => $entity === 'round' ? $model->id : $model->round_id]);
                }
                $transaction->rollBack();
            } catch (\Throwable $e) { $transaction->rollBack(); throw $e; }
        }
        return $this->render('edit', ['entity' => $entity, 'model' => $model, 'round' => $round ?? null, 'types' => ExamType::find()->orderBy('name')->all(), 'schedule' => $schedule]);
    }
    public function actionApplications()
    {
        $q = Yii::$app->request->get('q', '');
        $status = Yii::$app->request->get('status', '');
        $roundId = Yii::$app->request->get('round', '');
        $q = is_string($q) ? mb_substr(trim($q), 0, 190) : '';
        $status = is_string($status) && isset(\app\helpers\Ui::APPLICATION_STATUSES[$status]) ? $status : '';
        $roundId = is_scalar($roundId) && ctype_digit((string) $roundId) ? (string) $roundId : '';
        $query = Application::find()->alias('a')->select('a.*')->joinWith(['applicant u'])->with(['round', 'items.subject']);
        if ($q !== '') $query->andWhere(['or', ['like', 'a.reference', $q], ['like', 'u.name', $q]]);
        if ($status !== '') $query->andWhere(['a.status' => $status]);
        if ($roundId !== '') $query->andWhere(['a.round_id' => (int) $roundId]);
        $provider = new \yii\data\ActiveDataProvider([
            'query' => $query->orderBy(['a.id' => SORT_DESC]),
            'pagination' => ['pageSize' => 25],
        ]);
        return $this->render('applications', ['provider' => $provider, 'q' => $q, 'status' => $status, 'roundId' => $roundId,
            'rounds' => ExamRound::find()->orderBy(['id' => SORT_DESC])->all(),
            'statusCounts' => (new \yii\db\Query())->select(['status', 'count' => 'COUNT(*)'])->from('application')->groupBy('status')->indexBy('status')->all(),
        ]);
    }
    public function actionSaveTicket($id)
    {
        $application = Application::findOne((int) $id);
        if (!$application) throw new NotFoundHttpException();
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand('SELECT id FROM exam_round WHERE id=:id FOR UPDATE', [':id' => $application->round_id])->queryScalar();
            Yii::$app->db->createCommand('SELECT id FROM application WHERE id=:id FOR UPDATE', [':id' => $application->id])->queryScalar();
            $application->refresh();
            $ticket = $application->ticket ?? new \app\models\ExamTicket();
            $ticket->application_id = $application->id;
            $ticket->round_id = $application->round_id;
            $data = Yii::$app->request->post('ExamTicket');
            if (is_array($data)) {
                $ticket->setAttributes(array_intersect_key($data, array_flip(['venue', 'room', 'seat', 'note', 'published'])));
                if (!$ticket->exam_number && $application->isEligible()) $ticket->exam_number = (new \app\services\ExamNumberService())->issue($application->round_id);
            }
            if (is_array($data) && $ticket->validate()) {
                if ($ticket->published && !$application->isEligible()) throw new \DomainException('อนุมัติคุณสมบัติและตรวจรับชำระเงินก่อนเปิดพิมพ์บัตรสอบ');
                $ticket->updated_at = date('Y-m-d H:i:s');
                $ticket->save(false);
                \app\services\AuditService::record('exam.ticket_save', 'application', $application->id, ['published' => (bool) $ticket->published, 'exam_number' => $ticket->exam_number, 'venue' => $ticket->venue, 'room' => $ticket->room, 'seat' => $ticket->seat]);
                $transaction->commit();
                Yii::$app->session->setFlash('success', $ticket->published ? 'บันทึกและเปิดพิมพ์บัตรประจำตัวสอบแล้ว' : 'บันทึกข้อมูลบัตรสอบแล้ว ยังไม่เปิดพิมพ์');
                return $this->redirect(['/applicant/view', 'id' => $application->id, '#' => 'ticket-settings']);
            }
            $transaction->rollBack();
            return $this->render('../applicant/view', ['application' => $application, 'ticketForm' => $ticket]);
        } catch (\DomainException $e) {
            if ($transaction->isActive) $transaction->rollBack();
            $ticket->addError('published', $e->getMessage());
            return $this->render('../applicant/view', ['application' => $application, 'ticketForm' => $ticket]);
        } catch (\yii\db\IntegrityException $e) {
            if ($transaction->isActive) $transaction->rollBack();
            $ticket->addError('exam_number', 'เลขประจำตัวสอบซ้ำหรือข้อมูลไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง');
            return $this->render('../applicant/view', ['application' => $application, 'ticketForm' => $ticket]);
        } catch (\Throwable $e) { if ($transaction->isActive) $transaction->rollBack(); throw $e; }
    }
    private function findRound($id): ExamRound
    {
        $round = ExamRound::find()->where(['id' => (int) $id])->with(['type', 'subjects', 'milestones'])->one();
        if (!$round) throw new NotFoundHttpException('ไม่พบรอบสอบ');
        return $round;
    }
}
