<?php
namespace app\controllers;
use app\models\{Application, ApplicationSubject, ApplicationResult, ExamRoom, ExamRound, ExamTicket, Subject, User, StaffUserForm};
use app\services\{AuditService, ReviewService, ExamOperationsService, ResultService};
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use yii\web\{Controller, NotFoundHttpException};
class OperationsController extends Controller
{
    public function behaviors() { return [
        'verbs' => ['class' => \yii\filters\VerbFilter::class, 'actions' => array_fill_keys(['review', 'save-room', 'save-number-settings', 'allocate', 'publish-tickets', 'save-scores', 'save-outcomes', 'publish-results', 'save-user'], ['post'])],
        'access' => ['class' => \yii\filters\AccessControl::class, 'denyCallback' => function () { if (Yii::$app->user->isGuest) return $this->redirect(['/site/staff-login']); throw new \yii\web\ForbiddenHttpException('คุณไม่มีสิทธิ์ใช้งานส่วนนี้'); },
            'rules' => [['allow' => true, 'roles' => ['@'], 'matchCallback' => fn() => Yii::$app->user->identity->can($this->permission())]]],
    ]; }
    private function permission(): string
    {
        return match ($this->action->id) {
            'review' => match ((string) Yii::$app->request->post('kind')) { 'qualification' => 'qualification.review', 'document' => 'documents.review', 'payment' => 'payments.review', default => 'deny' },
            'rooms', 'save-room', 'save-number-settings', 'allocate', 'publish-tickets', 'roster' => 'exam.manage',
            'scores', 'outcomes' => 'results.view', 'save-scores', 'save-outcomes' => 'results.manage',
            'publish-results' => 'results.publish', 'reports', 'export' => 'reports.view', 'users', 'save-user' => 'users.manage', 'audit' => 'audit.view', default => 'deny',
        };
    }
    private function round($id = null): ?ExamRound
    {
        $id = $id ?? Yii::$app->request->get('round');
        if (!$id) return ExamRound::find()->orderBy(['id' => SORT_DESC])->one();
        if (!is_scalar($id) || !$round = ExamRound::findOne((int) $id)) throw new NotFoundHttpException('ไม่พบรอบสอบ');
        return $round;
    }
    private function rounds(): array { return ExamRound::find()->orderBy(['id' => SORT_DESC])->all(); }
    private function perform(callable $action, array $back)
    {
        try { $message = $action(); Yii::$app->session->setFlash('success', $message ?: 'บันทึกข้อมูลแล้ว'); }
        catch (\DomainException $e) { Yii::$app->session->setFlash('error', $e->getMessage()); }
        return $this->redirect($back);
    }
    public function actionReview($id)
    {
        return $this->perform(function () use ($id) {
            (new ReviewService())->decide((int) $id, (string) Yii::$app->request->post('kind'), (string) Yii::$app->request->post('decision'), (string) Yii::$app->request->post('reason'), (string) Yii::$app->request->post('revision'), Yii::$app->request->post('document') ? (int) Yii::$app->request->post('document') : null);
            return 'บันทึกผลพิจารณาแล้ว';
        }, ['/applicant/view', 'id' => $id, '#' => 'review-section']);
    }
    public function actionRooms()
    {
        $round = $this->round();
        $room = new ExamRoom();
        if ($id = Yii::$app->request->get('edit')) {
            $room = ExamRoom::findOne(['id' => (int) $id, 'round_id' => $round?->id]);
            if (!$room) throw new NotFoundHttpException();
        }
        return $this->render('rooms', ['round' => $round, 'rounds' => $this->rounds(), 'room' => $room,
            'rooms' => $round ? ExamRoom::find()->where(['round_id' => $round->id])->orderBy('id')->all() : [],
            'tickets' => $round ? ExamTicket::find()->where(['round_id' => $round->id])->count() : 0]);
    }
    public function actionSaveRoom($round)
    {
        $round = $this->round($round);
        $room = Yii::$app->request->post('id') ? ExamRoom::findOne(['id' => (int) Yii::$app->request->post('id'), 'round_id' => $round->id]) : new ExamRoom();
        if (!$room) throw new NotFoundHttpException();
        $room->round_id = $round->id;
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand('SELECT id FROM exam_round WHERE id=:id FOR UPDATE', [':id' => $round->id])->queryScalar();
            if ($room->load(Yii::$app->request->post()) && $room->validate()) {
                if (!$room->isNewRecord && ExamTicket::find()->where(['room_id' => $room->id])->exists() && ($room->isAttributeChanged('name') || $room->isAttributeChanged('venue'))) throw new \DomainException('ห้องที่จัดผู้เข้าสอบแล้วเปลี่ยนชื่อหรือสถานที่ไม่ได้');
                $room->save(false); AuditService::record('exam.room_save', 'exam_room', $room->id, ['name' => $room->name, 'capacity' => $room->capacity]); $transaction->commit();
                return $this->redirect(['rooms', 'round' => $round->id]);
            }
            $transaction->rollBack();
        } catch (\DomainException $e) { $transaction->rollBack(); $room->addError('name', $e->getMessage()); }
        catch (\Throwable $e) { if ($transaction->isActive) $transaction->rollBack(); throw $e; }
        return $this->render('rooms', ['round' => $round, 'rounds' => $this->rounds(), 'room' => $room, 'rooms' => ExamRoom::findAll(['round_id' => $round->id]), 'tickets' => ExamTicket::find()->where(['round_id' => $round->id])->count()]);
    }
    public function actionSaveNumberSettings($round)
    {
        return $this->perform(function () use ($round) {
            $form = new \app\models\ExamNumberSettingsForm();
            if (!$form->load(Yii::$app->request->post())) throw new \DomainException('ข้อมูลตั้งค่าเลขประจำตัวสอบไม่ถูกต้อง');
            (new \app\services\ExamNumberService())->configure((int) $round, $form);
            return 'บันทึกการรันเลขประจำตัวสอบแล้ว';
        }, ['rooms', 'round' => $round, '#' => 'exam-number-settings']);
    }
    public function actionAllocate($round) { return $this->perform(fn() => 'จัดที่นั่งเพิ่ม ' . (new ExamOperationsService())->allocate((int) $round) . ' คน', ['rooms', 'round' => $round]); }
    public function actionPublishTickets($round) { return $this->perform(fn() => 'บันทึกสถานะบัตรสอบ ' . (new ExamOperationsService())->publish((int) $round, Yii::$app->request->post('publish') === '1') . ' รายการ', ['rooms', 'round' => $round]); }
    public function actionRoster($id)
    {
        $room = ExamRoom::findOne((int) $id); if (!$room) throw new NotFoundHttpException();
        AuditService::record('exam.roster_view', 'exam_room', $id);
        return $this->render('roster', ['room' => $room, 'round' => $this->round($room->round_id), 'tickets' => ExamTicket::find()->where(['room_id' => $id])->orderBy(new \yii\db\Expression('CAST(seat AS UNSIGNED)'))->all()]);
    }
    private function eligibleQuery(int $roundId)
    {
        return Application::find()->alias('a')->select('a.*')->innerJoin('application_payment p', 'p.application_id=a.id')->where(['a.round_id' => $roundId, 'a.qualification_status' => 'approved', 'p.status' => 'verified']);
    }
    public function actionScores()
    {
        $round = $this->round();
        $subjects = $round?->subjects ?? [];
        $subjectId = (int) Yii::$app->request->get('subject', $subjects[0]->id ?? 0);
        $query = ApplicationSubject::find()->alias('i')->select('i.*')->innerJoin(['a' => $this->eligibleQuery($round?->id ?? 0)], 'a.id=i.application_id')->where(['i.subject_id' => $subjectId])->with(['application.applicant', 'application.ticket', 'subject', 'score'])->orderBy('i.id');
        return $this->render('scores', ['round' => $round, 'rounds' => $this->rounds(), 'subjects' => $subjects, 'subjectId' => $subjectId, 'provider' => new ActiveDataProvider(['query' => $query, 'pagination' => ['pageSize' => 100]])]);
    }
    public function actionSaveScores($round)
    {
        return $this->perform(function () use ($round) { $rows = Yii::$app->request->post('Scores', []); if (!is_array($rows)) throw new \DomainException('ข้อมูลคะแนนไม่ถูกต้อง'); (new ResultService())->saveScores((int) $round, $rows, (string) Yii::$app->request->post('revision')); return 'บันทึกคะแนนและการเข้าสอบแล้ว'; }, ['scores', 'round' => $round, 'subject' => (int) Yii::$app->request->post('subject')]);
    }
    public function actionOutcomes()
    {
        $round = $this->round();
        return $this->render('outcomes', ['round' => $round, 'rounds' => $this->rounds(), 'provider' => new ActiveDataProvider(['query' => $this->eligibleQuery($round?->id ?? 0)->with(['applicant', 'items.subject', 'items.score', 'result', 'ticket'])->orderBy('a.id'), 'pagination' => ['pageSize' => 100]])]);
    }
    public function actionSaveOutcomes($round)
    {
        return $this->perform(function () use ($round) { $rows = Yii::$app->request->post('Outcomes', []); if (!is_array($rows)) throw new \DomainException('ข้อมูลผลสอบไม่ถูกต้อง'); $rows = array_filter($rows, fn($row) => is_array($row) && ($row['outcome'] ?? '') !== ''); (new ResultService())->saveOutcomes((int) $round, $rows, (string) Yii::$app->request->post('revision')); return 'บันทึกผลคัดเลือกแล้ว ยังไม่ประกาศจนกว่าจะกดเปิดประกาศผล'; }, ['outcomes', 'round' => $round]);
    }
    public function actionPublishResults($round) { return $this->perform(function () use ($round) { (new ResultService())->publish((int) $round, Yii::$app->request->post('publish') === '1', (string) Yii::$app->request->post('revision')); return 'บันทึกสถานะประกาศผลแล้ว'; }, ['outcomes', 'round' => $round]); }
    private function reportRows(?int $roundId): array
    {
        $query = (new Query())->from('application a')->innerJoin('exam_round r', 'r.id=a.round_id')->leftJoin('application_payment p', 'p.application_id=a.id')->leftJoin('admission_program g', 'g.id=a.program_id')->select(['round_name' => 'r.name', 'program' => 'g.code', 'applications' => 'COUNT(*)', 'qualified' => "SUM(a.qualification_status='approved')", 'verified' => "SUM(CASE WHEN p.status='verified' THEN 1 ELSE 0 END)", 'income' => "SUM(CASE WHEN p.status='verified' THEN p.amount ELSE 0 END)", 'pending' => "SUM(CASE WHEN p.status='pending_review' THEN 1 ELSE 0 END)"])->groupBy(['r.id', 'r.name', 'g.id', 'g.code'])->orderBy('r.id');
        if ($roundId) $query->andWhere(['r.id' => $roundId]);
        return $query->all();
    }
    public function actionReports()
    {
        $round = Yii::$app->request->get('round') ? $this->round() : null;
        $results = (new Query())->from('application_result x')->innerJoin('application a', 'a.id=x.application_id')->select(['outcome', 'count' => 'COUNT(*)'])->groupBy('outcome');
        $attendance = (new Query())->from('application_subject i')->innerJoin('application a', 'a.id=i.application_id')->innerJoin('application_payment p', "p.application_id=a.id AND p.status='verified'")->leftJoin('exam_score s', 's.item_id=i.id')->where(['a.qualification_status' => 'approved'])->select(['attendance' => new \yii\db\Expression("COALESCE(s.attendance, 'pending')"), 'count' => 'COUNT(*)'])->groupBy(new \yii\db\Expression("COALESCE(s.attendance, 'pending')"));
        if ($round) { $results->andWhere(['a.round_id' => $round->id]); $attendance->andWhere(['a.round_id' => $round->id]); }
        return $this->render('reports', ['round' => $round, 'rounds' => $this->rounds(), 'rows' => $this->reportRows($round?->id), 'resultCounts' => $results->indexBy('outcome')->all(), 'attendanceCounts' => $attendance->indexBy('attendance')->all()]);
    }
    public function actionExport()
    {
        $round = Yii::$app->request->get('round') ? $this->round() : null;
        $rows = $this->reportRows($round?->id);
        $file = fopen('php://temp', 'r+'); fwrite($file, "\xEF\xBB\xBF");
        fputcsv($file, ['รอบสอบ', 'โครงการ', 'ใบสมัคร', 'ผ่านคุณสมบัติ', 'ตรวจรับชำระแล้ว', 'รายรับตรวจรับแล้ว', 'รอตรวจชำระเงิน'], ',', '"', '');
        foreach ($rows as $row) fputcsv($file, array_map(fn($value) => is_string($value) && preg_match('/^[=+@\-\t\r\n]/u', $value) ? "'" . $value : $value, array_values($row)), ',', '"', '');
        rewind($file); $content = stream_get_contents($file); fclose($file);
        AuditService::record('reports.export', 'exam_round', $round?->id ?? 'all');
        return Yii::$app->response->sendContentAsFile($content, 'admission-report.csv', ['mimeType' => 'text/csv; charset=UTF-8']);
    }
    public function actionUsers()
    {
        $user = Yii::$app->request->get('edit') ? User::findOne((int) Yii::$app->request->get('edit')) : null;
        if ($user && !$user->isStaff()) throw new NotFoundHttpException();
        $form = new StaffUserForm(['scenario' => $user ? 'default' : 'create']);
        if ($user) $form->setAttributes($user->getAttributes(['name', 'email', 'role', 'active']));
        return $this->render('users', ['users' => User::find()->where(['role' => array_keys(User::STAFF_ROLES)])->orderBy('id')->all(), 'form' => $form, 'editing' => $user]);
    }
    public function actionSaveUser()
    {
        $user = Yii::$app->request->post('id') ? User::findOne((int) Yii::$app->request->post('id')) : new User();
        if (!$user || !$user->isNewRecord && !$user->isStaff()) throw new NotFoundHttpException();
        $form = new StaffUserForm(['scenario' => $user->isNewRecord ? 'create' : 'default']);
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand("SELECT id FROM user WHERE role='admin' ORDER BY id FOR UPDATE")->queryColumn();
            if ($form->load(Yii::$app->request->post()) && $form->validate()) {
                if ((int) $user->id === (int) Yii::$app->user->id && (!$form->active || $form->role !== 'admin')) throw new \DomainException('ไม่สามารถปิดบัญชีหรือลดสิทธิ์ผู้ดูแลของตนเอง');
                if (!$user->isNewRecord && $user->role === 'admin' && (!$form->active || $form->role !== 'admin') && User::find()->where(['role' => 'admin', 'active' => 1])->andWhere(['<>', 'id', $user->id])->count() < 1) throw new \DomainException('ต้องมีผู้ดูแลระบบที่ใช้งานได้อย่างน้อย 1 คน');
                $before = $user->getAttributes(['role', 'active']);
                $user->setAttributes($form->getAttributes(['name', 'email']), false); $user->role = $form->role; $user->active = (bool) $form->active;
                if ($form->password !== '') { $user->password_hash = Yii::$app->security->generatePasswordHash($form->password); $user->auth_key = Yii::$app->security->generateRandomString(); }
                if ($user->isNewRecord) { $user->auth_key = Yii::$app->security->generateRandomString(); $user->created_at = date('Y-m-d H:i:s'); }
                if ($user->save()) { AuditService::record('users.save', 'user', $user->id, ['before' => $before, 'after' => ['role' => $user->role, 'active' => $user->active], 'password_changed' => $form->password !== '']); $transaction->commit(); return $this->redirect(['users']); }
                $form->addErrors($user->getErrors());
            }
            $transaction->rollBack();
        } catch (\DomainException $e) { $transaction->rollBack(); $form->addError('role', $e->getMessage()); }
        catch (\yii\db\IntegrityException $e) { if ($transaction->isActive) $transaction->rollBack(); $form->addError('email', 'อีเมลนี้ถูกใช้แล้ว กรุณาตรวจสอบอีกครั้ง'); }
        catch (\Throwable $e) { if ($transaction->isActive) $transaction->rollBack(); throw $e; }
        $form->password = '';
        return $this->render('users', ['users' => User::find()->where(['role' => array_keys(User::STAFF_ROLES)])->all(), 'form' => $form, 'editing' => $user->isNewRecord ? null : $user]);
    }
    public function actionAudit()
    {
        $query = (new Query())->from('audit_log')->orderBy(['id' => SORT_DESC]);
        $action = Yii::$app->request->get('event', ''); $action = is_string($action) ? $action : '';
        if ($action !== '') $query->andWhere(['action' => $action]);
        return $this->render('audit', ['provider' => new \yii\data\ActiveDataProvider(['query' => $query, 'pagination' => ['pageSize' => 50]]), 'event' => $action, 'events' => (new Query())->select('action')->from('audit_log')->distinct()->column()]);
    }
}
