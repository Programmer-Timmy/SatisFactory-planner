<?php

if (!isset($_POST['productionLineId']) || !isset($_POST['checklist'])) {
    echo json_encode(['error' => 'Invalid request. You must provide a productionLineId and a checklist in post data.']);
    http_response_code(400);
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
