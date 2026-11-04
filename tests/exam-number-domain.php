<?php
$token = 'number' . bin2hex(random_bytes(4));
$argv[1] = $token;
ob_start(); require __DIR__ . '/operations-fixture.php'; $fixture = json_decode(ob_get_clean(), true);
use app\models\{Application, ExamRoom, ExamRound, ExamTicket, ExamNumberSettingsForm};
use app\services\{ExamNumberService, ExamOperationsService};
$db = Yii::$app->db;
$tx = $db->beginTransaction();
$checks = 0;
function numberCheck(bool $ok, string $message): void { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
function numberRejected(callable $call): void { try { $call(); } catch (DomainException $e) { numberCheck(true, $e->getMessage()); return; } throw new RuntimeException('Expected rejection'); }
try {
    $id = (int) $fixture['roundId'];
    $service = new ExamNumberService();
    numberRejected(fn() => $service->configure($id, new ExamNumberSettingsForm(['prefix' => 'X', 'digits' => 2, 'start' => 100])));
    $service->configure($id, new ExamNumberSettingsForm(['prefix' => 'M4-', 'digits' => 4, 'start' => 25]));
    $app = Application::findOne($fixture['applicationId']);
    $app->qualification_status = 'approved'; $app->save(false);
    $app->payment->status = 'verified'; $app->payment->save(false);
    $room = new ExamRoom(['round_id' => $id, 'venue' => 'Test', 'name' => '101', 'capacity' => 1]); $room->save();
    $draft = new ExamTicket(['application_id' => $app->id, 'round_id' => $id, 'room_id' => $room->id, 'venue' => 'Test', 'room' => '101', 'seat' => '1', 'published' => false, 'updated_at' => date('Y-m-d H:i:s')]);
    numberCheck($draft->save(), 'Unnumbered draft saved');
    numberCheck((new ExamOperationsService())->allocate($id) === 1, 'Eligible candidate allocated');
    $ticket = ExamTicket::findOne($app->id);
    numberCheck($ticket->exam_number === 'M4-0025', 'Configured prefix, padding and start used');
    numberCheck((int) ExamRound::findOne($id)->exam_number_next === 26, 'Counter advanced');
    numberRejected(fn() => $service->configure($id, new ExamNumberSettingsForm(['prefix' => 'CHANGED', 'digits' => 4, 'start' => 1])));
    numberCheck((new ExamOperationsService())->allocate($id) === 0, 'Repeat allocation is idempotent');
    numberCheck(ExamTicket::findOne($app->id)->exam_number === 'M4-0025', 'Issued number preserved');
    $nested = $db->beginTransaction();
    numberCheck($service->issue($id) === 'M4-0026', 'Sequence increments');
    $nested->rollBack();
    numberCheck((int) ExamRound::findOne($id)->exam_number_next === 26, 'Rollback does not consume sequence');
    ExamRound::updateAll(['exam_number_next' => 25], ['id' => $id]);
    numberCheck($service->issue($id) === 'M4-0026', 'Existing number collision skipped');
    ExamRound::updateAll(['exam_number_digits' => 1, 'exam_number_next' => 10], ['id' => $id]);
    numberRejected(fn() => $service->issue($id));
    echo "$checks exam numbering checks passed; all fixture changes rolled back.\n";
} finally {
    if ($tx->isActive) $tx->rollBack();
    passthru(PHP_BINARY . ' ' . escapeshellarg(__DIR__ . '/cleanup.php') . ' ' . escapeshellarg($token));
}
