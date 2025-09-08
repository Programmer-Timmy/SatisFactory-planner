<?php
if (!$_POST) {
    http_response_code(400);
    echo json_encode(['error' => 'No data provided']);
    exit;
}

if (!isset($_POST['powerProductionId']) || !is_numeric($_POST['powerProductionId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No power production id provided']);
    exit;
}

if (!isset($_POST['amount']) || !is_numeric($_POST['amount'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No amount provided']);
    exit;
}

if (!isset($_POST['clockSpeed']) || !is_numeric($_POST['clockSpeed'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No clock speed provided']);
    exit;
}

if (!isset($_POST['gameSaveId']) || !is_numeric($_POST['gameSaveId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No game save id provided']);
    exit;
}

$gameSaveId = $_POST['gameSaveId'];

if (!GameSaves::checkAccess($gameSaveId, $_SESSION['userId'], Permission::SAVEGAME_EDIT)) {
    http_response_code(403);
    echo json_encode(['error' => 'You do not have access to this save game']);
    exit;
}

$powerProductionId = $_POST['powerProductionId'];
$amount = $_POST['amount'];
$clockSpeed = $_POST['clockSpeed'];

try {
    Database::update('power_production', ['amount', 'clock_speed'], [$amount, $clockSpeed], ['id' => $powerProductionId]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to update power production']);
    exit;
}
echo json_encode(['success' => true]);
exit;