<?php
$building = Buildings::getBuildingById($_GET['id']);

if (empty($building)) {
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

echo json_encode($building);