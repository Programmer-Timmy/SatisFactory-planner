<?php
if (!$_POST) {
    echo json_encode(['error' => 'No data provided']);
    exit;
}

$gameSaveId = $_POST['gameSaveId'];
$buildingId = $_POST['buildingId'];
$amount = $_POST['amount'];
$clockSpeed = $_POST['clockSpeed'];

$powerProductionId = PowerProduction::addPowerProduction($gameSaveId, $buildingId, $amount, $clockSpeed);

echo json_encode(['success' => true, 'x' => $powerProductionId]);
exit;