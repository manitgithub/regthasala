<?php
use app\helpers\Ui;
use yii\helpers\{Html, Url};
$user = Yii::$app->user;
$admin = !$user->isGuest && $user->identity->isStaff();
$inAdmin = $this->context->id === 'admin';
$schoolName = Yii::$app->params['schoolName'];
$systemName = Yii::$app->params['systemName'];
$this->beginPage();
?>
<!doctype html>
<html lang="th"><head><meta charset="<?= Yii::$app->charset ?>"><meta name="viewport" content="width=device-width, initial-scale=1">
<?= Html::csrfMetaTags() ?><title><?= Html::encode($this->title) ?> · <?= Html::encode(Yii::$app->name) ?></title>
<link rel="icon" type="image/png" href="/images/thasala-crest.png"><meta name="theme-color" content="#303238">
<link rel="stylesheet" href="/css/app.css?v=<?= filemtime(Yii::getAlias('@app/web/css/app.css')) ?>"><script defer src="/js/app.js?v=<?= filemtime(Yii::getAlias('@app/web/js/app.js')) ?>"></script><?php $this->head() ?></head>
<body><?php $this->beginBody() ?>
<a href="#main" class="skip-link">ข้ามไปเนื้อหาหลัก</a>
<div class="app-shell">
<aside class="sidebar">
    <div class="brand-row">
    <a class="brand" href="<?= Url::to(['/site/index']) ?>"><img class="school-crest" src="/images/thasala-crest.png" alt="ตราโรงเรียนท่าศาลาประสิทธิ์ศึกษา" width="48" height="56"><span class="brand-text"><?= Html::encode($schoolName) ?><small><?= Html::encode($systemName) ?></small></span></a>
    <button class="mobile-nav-toggle" type="button" aria-label="เปิดเมนูหลัก" aria-controls="navigation-panel" aria-expanded="false" title="เปิดเมนูหลัก"><?= Ui::icon('menu') ?></button></div>
    <div id="navigation-panel" class="navigation-panel">
    <div class="nav-label"><?= $admin ? 'จัดการระบบรับสมัคร' : 'สำหรับผู้สมัครสอบ' ?></div>
    <nav aria-label="เมนูหลัก">
    <?php if ($admin): ?>
        <?= Ui::link('การสอบและรอบสอบ', ['/admin/index'], 'calendar-range', 'nav-item ' . ($inAdmin && $this->context->action->id !== 'applications' ? 'is-active' : '')) ?>
        <?php if ($user->identity->can('applications.view')): ?><?= Ui::link('ใบสมัครทั้งหมด', ['/admin/applications'], 'files', 'nav-item ' . ($this->context->action->id === 'applications' || $this->context->id === 'applicant' && $this->context->action->id === 'view' ? 'is-active' : '')) ?><?php endif ?>
        <?php foreach (['exam.manage' => ['จัดสอบและห้องสอบ', 'rooms', 'graduation-cap'], 'results.view' => ['คะแนนและประกาศผล', 'outcomes', 'file-check-2'], 'reports.view' => ['รายงานผล', 'reports', 'chart-no-axes-column'], 'users.manage' => ['ผู้ใช้งานและสิทธิ์', 'users', 'users'], 'audit.view' => ['ประวัติการใช้งาน', 'audit', 'history']] as $permission => [$label, $action, $icon]): if ($user->identity->can($permission)): ?><?= Ui::link($label, ['/operations/' . $action], $icon, 'nav-item ' . ($this->context->id === 'operations' && ($this->context->action->id === $action || $action === 'outcomes' && $this->context->action->id === 'scores') ? 'is-active' : '')) ?><?php endif; endforeach ?>
    <?php endif ?>
    <?= Ui::link('รอบสอบที่เปิดรับ', ['/site/index'], 'book-open', 'nav-item ' . ($this->context->id === 'site' && $this->context->action->id === 'index' ? 'is-active' : '')) ?>
    <?php if (!$admin): ?>
    <?= Ui::link('ตรวจสอบสถานะ', ['/site/status'], 'file-check-2', 'nav-item ' . ($this->context->id === 'site' && in_array($this->context->action->id, ['status', 'login']) || $this->context->id === 'applicant' && in_array($this->context->action->id, ['index', 'view']) ? 'is-active' : '')) ?>
    <?= Ui::link('พิมพ์บัตรประจำตัวสอบ', ['/site/exam-card'], 'printer', 'nav-item ' . ($this->context->id === 'site' && $this->context->action->id === 'exam-card' || $this->context->id === 'applicant' && in_array($this->context->action->id, ['tickets', 'ticket']) ? 'is-active' : '')) ?>
    <?= Ui::link('ผลสอบ', ['/site/results'], 'file-check-2', 'nav-item ' . ($this->context->action->id === 'results' ? 'is-active' : '')) ?>
    <?php endif ?>
    </nav>
    <div class="sidebar-bottom">
    <?php if ($user->isGuest): ?>
        <?= Ui::link('สำหรับเจ้าหน้าที่', ['/site/staff-login'], 'log-in', 'nav-item') ?>
    <?php else: ?>
        <div class="account"><span class="avatar"><?= Html::encode(mb_substr($user->identity->name, 0, 1)) ?></span><span class="min-w-0"><strong><?= Html::encode($user->identity->name) ?></strong><small><?= $admin ? Html::encode(\app\models\User::STAFF_ROLES[$user->identity->role]) : 'ผู้สมัครสอบ' ?></small></span></div>
        <?= Html::beginForm(['/site/logout'], 'post') ?><button class="nav-item w-full" type="submit"><?= Ui::icon('log-out') ?>ออกจากระบบ</button><?= Html::endForm() ?>
    <?php endif ?>
    </div>
    </div>
</aside>
<div class="workspace min-w-0">
    <header class="utility-bar"><span class="flex items-center gap-2 min-w-0"><?= Ui::icon($admin ? 'layers-2' : 'building-2') ?><span><?= $admin ? 'ระบบบริหารการรับสมัคร' : Html::encode($schoolName) ?></span></span><div class="utility-meta"><?php if ($admin): ?><span class="staff-label"><?= Html::encode(\app\models\User::STAFF_ROLES[$user->identity->role]) ?></span><?php endif ?><time><?= Ui::date(date('Y-m-d H:i:s'), false) ?></time></div></header>
    <main id="main" class="main-content">
        <?php foreach (Yii::$app->session->getAllFlashes() as $kind => $message): ?><div class="alert <?= $kind === 'success' ? 'alert-success' : ($kind === 'error' ? 'alert-error' : '') ?>" role="status"><?= Ui::icon($kind === 'error' ? 'circle-alert' : 'circle-check') ?><?= Html::encode($message) ?></div><?php endforeach ?>
        <?= $content ?>
    </main>
    <footer class="footer"><span><?= Html::encode($schoolName) ?> · ท่าศาลา นครศรีธรรมราช</span><span><?= Html::encode($systemName) ?></span></footer>
</div></div>
<?php $this->endBody() ?></body></html><?php $this->endPage() ?>
