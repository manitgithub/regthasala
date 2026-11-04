<?php
$token = base_convert((string) time(), 10, 36) . bin2hex(random_bytes(2));
$argv[1] = $token;
ob_start(); require __DIR__ . '/operations-fixture.php'; $fixture = json_decode(ob_get_clean(), true);
use app\models\{Application, User, ExamRoom, ExamTicket, ApplicationSubject, ExamScore, ApplicationResult};
use app\services\{ReviewService, ExamOperationsService, ResultService};
Yii::$app->set('user', ['class' => yii\web\User::class, 'identityClass' => User::class, 'enableSession' => false]);
Yii::$app->user->setIdentity(User::findOne(['role' => 'admin', 'active' => 1]));
$checks = 0;
function opsCheck(bool $condition, string $message): void { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
function opsRejected(callable $call): void { try { $call(); } catch (DomainException $e) { opsCheck(true, $e->getMessage()); return; } throw new RuntimeException('Expected a rejected operation'); }
$tx = Yii::$app->db->beginTransaction();
try {
    $app = Application::findOne($fixture['applicationId']); $review = new ReviewService();
    opsRejected(fn() => $review->decide($app->id, 'qualification', 'approved', '', ReviewService::revision($app)));
    opsRejected(fn() => $review->decide($app->id, 'qualification', 'rejected', '', ReviewService::revision($app)));
    $revision = ReviewService::revision($app);
    $review->decide($app->id, 'document', 'accepted', '', $revision, $fixture['documents']['photo']);
    $app->refresh();
    opsRejected(fn() => $review->decide($app->id, 'qualification', 'approved', '', $revision));
    $review->decide($app->id, 'qualification', 'approved', '', ReviewService::revision($app)); $app->refresh();
    opsCheck(!$app->isEligible(), 'Qualification alone must not grant exam eligibility');
    $review->decide($app->id, 'payment', 'verified', '', ReviewService::revision($app)); $app->refresh();
    opsCheck($app->isEligible() && $app->status === 'eligible', 'Both approvals grant eligibility');
    $operations = new ExamOperationsService();
    opsRejected(fn() => $operations->allocate($fixture['roundId']));
    $room = new ExamRoom(['round_id' => $fixture['roundId'], 'venue' => 'Test', 'name' => 'A', 'capacity' => 0]); opsCheck(!$room->validate(), 'Zero-capacity room rejected'); $room->capacity = 1; $room->save();
    opsCheck($operations->allocate($fixture['roundId']) === 1, 'One candidate gets a seat');
    opsCheck($operations->allocate($fixture['roundId']) === 0, 'Allocation never overwrites an existing ticket');
    $room->capacity = 0; opsCheck(!$room->validate(), 'Cannot shrink room below assigned candidates');
    $operations->publish($fixture['roundId'], true); $app->refresh();
    opsRejected(fn() => $review->decide($app->id, 'payment', 'rejected', 'invalid', ReviewService::revision($app)));
    $operations->publish($fixture['roundId'], false);
    $service = new ResultService(); $item = $app->items[0];
    opsRejected(fn() => $service->saveScores($fixture['roundId'], [$item->id => ['attendance' => 'present', 'score' => '101']]));
    opsCheck(!ExamScore::findOne($item->id), 'Invalid scores are rolled back');
    opsRejected(fn() => $service->saveScores($fixture['roundId'], [$item->id => ['attendance' => 'absent', 'score' => '1']]));
    opsRejected(fn() => $service->publish($fixture['roundId'], true));
    $service->saveOutcomes($fixture['roundId'], [$app->id => ['outcome' => 'reserve', 'note' => 'Test']]);
    opsRejected(fn() => $service->publish($fixture['roundId'], true));
    $rows = []; foreach ($app->items as $record) $rows[$record->id] = ['attendance' => 'present', 'score' => '40.25'];
    $oldRevision = ResultService::revision($fixture['roundId']);
    $service->saveScores($fixture['roundId'], $rows);
    opsRejected(fn() => $service->saveScores($fixture['roundId'], $rows, $oldRevision));
    opsRejected(fn() => $service->publish($fixture['roundId'], true, $oldRevision));
    $service->publish($fixture['roundId'], true);
    opsRejected(fn() => $service->saveScores($fixture['roundId'], $rows));
    $service->publish($fixture['roundId'], false); $service->saveScores($fixture['roundId'], $rows);
    $finance = new User(['role' => 'finance', 'active' => 1]); opsCheck($finance->can('payments.review') && !$finance->can('qualification.review') && !$finance->can('users.manage'), 'Finance has separated permissions');
    $committee = new User(['role' => 'committee', 'active' => 1]); opsCheck($committee->can('results.publish') && !$committee->can('results.manage'), 'Committee may publish but cannot edit scores');
    $disabled = new User(['role' => 'admin', 'active' => 0]); opsCheck(!$disabled->can('users.manage'), 'Disabled administrator has no permissions');
    $staffForm = new \app\models\StaffUserForm(['scenario' => 'create', 'name' => 'Test', 'email' => 'test@example.test', 'password' => 'short']); opsCheck(!$staffForm->validate(), 'Short staff passwords rejected');
    echo "$checks operations domain checks passed; fixture changes rolled back.\n";
} finally {
    if ($tx->isActive) $tx->rollBack();
    passthru(PHP_BINARY . ' ' . escapeshellarg(__DIR__ . '/cleanup.php') . ' ' . escapeshellarg($token));
}
