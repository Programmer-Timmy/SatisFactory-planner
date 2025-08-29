<?php
require_once '../private/types/permission.php';

if (!$_POST) {
    http_response_code(400);
    echo json_encode(['error' => 'No data provided']);
    exit;
}

$gameSaveId = $_POST['gameSaveId'];

if (!GameSaves::checkAccess($gameSaveId, $_SESSION['userId'], Permission::SAVEGAME_EDIT)) {
    http_response_code(403);
    echo json_encode(['error' => 'You do not have access to this save game']);
    exit;
}
$powerProduction = PowerProduction::getPowerProduction($gameSaveId);
$totalPowerProduction = 0;
$bonus_percentage = 1;

foreach ($powerProduction as $production) {
    if ($production->class_name == 'Build_AlienPowerBuilding_C') {
        $bonus_percentage += 0.1 * $production->amount;
    } elseif ($production->class_name == 'Build_AlienPowerBuilding_C_Boosted') {
        $bonus_percentage += 0.3 * $production->amount;
    }
    $totalPowerProduction += $production->power_generation * $production->amount * ($production->clock_speed / 100);
}

$totalPowerProduction *= $bonus_percentage;

GameSaves::updatePowerProduction($gameSaveId, round($totalPowerProduction));

echo json_encode(['success' => true, 'totalPowerProduction' => round($totalPowerProduction)]);

