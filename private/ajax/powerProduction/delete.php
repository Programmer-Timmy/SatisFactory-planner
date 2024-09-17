<?php
if (!$_POST) {
    echo json_encode(['error' => 'No data provided']);
    exit;
}

$powerProductionId = $_POST['powerProductionId'];

PowerProduction::deletePowerProduction($powerProductionId);

echo json_encode(['success' => true]);
exit;