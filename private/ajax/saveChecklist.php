<?php

if (!isset($_POST['productionLineId']) || !isset($_POST['checklist'])) {
    echo json_encode(['error' => 'Invalid request. You must provide a productionLineId and a checklist in post data.']);
    http_response_code(400);
    exit();
}

$gameSaveId = ProductionLines::getGameSaveId($_POST['productionLineId']);
$access = ProductionLines::validateAccess($_POST['productionLineId'], $gameSaveId, $_SESSION['userId']);

if (!$access) {
    echo json_encode(['error' => 'You do not have permission to modify this production line.']);
    http_response_code(403);
    exit();
}

$productionLineId = $_POST['productionLineId'];
$checklist = $_POST['checklist'];

$checklist = json_decode($checklist, true);

if (Checklist::saveChecklist($checklist, $productionLineId)) {
    echo json_encode(['success' => 'Checklist saved successfully']);
} else {
    echo json_encode(['error' => 'Failed to save checklist']);
    http_response_code(500);
}
