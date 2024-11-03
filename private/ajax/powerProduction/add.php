<?php
if (!$_POST) {
    http_response_code(400);
    echo json_encode(['error' => 'No data provided']);
    exit;
}

if (!isset($_POST['gameSaveId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No game save id provided']);
    exit;
}

if (!isset($_POST['buildingId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No building id provided']);
    exit;
}

if (!isset($_POST['amount'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No amount provided']);
    exit;
}

if (!isset($_POST['clockSpeed'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No clock speed provided']);
    exit;
}

$gameSaveId = $_POST['gameSaveId'];

if (!GameSaves::checkAccessUser($gameSaveId)) {
    http_response_code(403);
    echo json_encode(['error' => 'You do not have access to this save game']);
    exit;
}

$buildingId = $_POST['buildingId'];
$amount = $_POST['amount'];
$clockSpeed = $_POST['clockSpeed'];

$powerProductionId = PowerProduction::addPowerProduction($gameSaveId, $buildingId, $amount, $clockSpeed);

echo json_encode(['success' => true, 'powerProductionId' => $powerProductionId]);
exit;