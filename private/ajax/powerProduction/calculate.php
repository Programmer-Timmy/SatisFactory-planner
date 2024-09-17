<?php
if (!$_POST) {
    echo json_encode(['error' => 'No data provided']);
    exit;
}

$gameSaveId = $_POST['gameSaveId'];
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

