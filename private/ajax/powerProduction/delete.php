<?php
require_once '../private/types/permission.php';

if (!$_POST) {
    http_response_code(400);
    echo json_encode(['error' => 'No data provided']);
    exit;
}

if (!isset($_POST['powerProductionId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No power production id provided']);
    exit;
}

if (!isset($_POST['gameSaveId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No game save id provided']);
    exit;
}

$gameSaveId = $_POST['gameSaveId'];
$powerProductionId = $_POST['powerProductionId'];

if (!GameSaves::checkAccess($gameSaveId, $_SESSION['userId'], Permission::SAVEGAME_EDIT)) {
    http_response_code(403);
    echo json_encode(['error' => 'You do not have permission to delete this power production']);
    exit;
}

PowerProduction::deletePowerProduction($powerProductionId);

echo json_encode(['success' => true]);
exit;