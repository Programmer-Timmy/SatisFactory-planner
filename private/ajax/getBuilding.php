<?php

$building = Buildings::getBuildingById($_GET['id']);

if (empty($building)) {
    header('Location: /');
    exit();
}

echo json_encode($building);