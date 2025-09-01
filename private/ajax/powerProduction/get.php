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

$gameSaveId = $_POST['gameSaveId'];

if (!GameSaves::checkAccess($gameSaveId, $_SESSION['userId'], Permission::SAVEGAME_READ)) {
    http_response_code(403);
    echo json_encode(['error' => 'You do not have access to this save game']);
    exit;
}

$gameSave = GameSaves::getSaveGameById($gameSaveId);

echo json_encode(['success' => true, 'powerProduction' => $gameSave->total_power_production]);
