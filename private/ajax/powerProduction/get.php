<?php
if (!$_POST) {
    echo json_encode(['error' => 'No data provided']);
    exit;
}

$gameSaveId = $_POST['gameSaveId'];
$gameSave = GameSaves::getSaveGameById($gameSaveId);


echo json_encode(['success' => true, 'powerProduction' => $gameSave->total_power_production]);
