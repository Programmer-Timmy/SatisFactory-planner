<?php
if (!$_POST) {
    echo json_encode(['error' => 'No data provided']);
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