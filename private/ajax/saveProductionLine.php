<?php

if (!isset($_POST['data']) || !isset($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in to edit production lines']);
    exit();
}

if (!isset($_POST['gameSaveId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No game save id provided']);
    exit();
}

if (!ProductionLines::checkProductionLineVisability($_POST['gameSaveId'], $_SESSION['userId'])) {
    http_response_code(403);
    echo json_encode(['error' => 'You do not have permission to edit this production line']);
    exit();
}

$data = json_decode($_POST['data'], true);
$importsData = [];
$productionData = [];
$powerData = [];
$totalPower = 0;

$productionLineId = $_POST['id'];

$importRows = $data['importsTableRows'];
foreach ($importRows as $row) {
    if ($row['itemId'] == null || $row['quantity'] == 0 || $row['quantity'] == '') {
        continue;
    }
    $importsData[] = (object)[
        'id' => intval($row['itemId']),
        'ammount' => $row['quantity']
    ];
}
$productionRows = $data['productionTableRows'];

foreach ($productionRows as $row) {
    if ($row['recipeId'] == null || $row['recipeId'] == 0 || $row['quantity'] == 0 || $row['quantity'] == '') {
        continue;
    }
    $secondUsage = $row['doubleExport'] == 'true' ? $row['extraCells']['Usage'] : null;
    $secondExport = $row['doubleExport'] == 'true' ? $row['extraCells']['ExportPerMin'] : null;
    $productionData[] = (object)[
        'id' => $row['row_id'],
        'recipe_id' => $row['recipeId'],
        'product_quantity' => $row['quantity'],
        'usage' => $row['Usage'],
        'export_amount_per_min' => $row['exportPerMin'],
        'local_usage2' => $secondUsage,
        'export_ammount_per_min2' => $secondExport,
        'produciton_settings' => [
            'clock_speed' => $row['recipeSetting']['clockSpeed'],
            'use_somersloop'=> $row['recipeSetting']['useSomersloop'],
        ]
    ];
}

$powerRows = $data['powerTableRows'];
foreach ($powerRows as $row) {
    if ($row['buildingId'] == null || $row['quantity'] == 0 || $row['quantity'] == '') {
        continue;
    }
    $powerData[] = (object)[
        'buildings_id' => $row['buildingId'],
        'building_ammount' => $row['quantity'],
        'clock_speed' => $row['clockSpeed'],
        'power_used' => $row['Consumption'],
        'user' => $row['userRow']
    ];
    $totalPower += $row['Consumption'];
}
$oldAndNewIds = ProductionLines::saveProductionLine($importsData, $productionData, $powerData, $totalPower, $productionLineId);
$checklist = $data['checklist'];
$checklists = [];
foreach ($checklist as $check) {
//    if id is in old id change it to the new id
    $newId = null;
    if ($oldAndNewIds !== false) {

        foreach ($oldAndNewIds as $oldAndNewId) {
            if ($oldAndNewId['old'] == $check['productionRow']['row_id']) {
                $newId = $oldAndNewId['new'];
                break;
            }
        }
    }

    $checklists[] = (object)[
        'productionRowId' => $newId ?? $check['productionRow']['row_id'],
        'beenBuild' => $check['beenBuild'],
        'beenTested' => $check['beenTested'],
    ];
}

if ($checklists) {

    if (!Checklist::saveChecklist($checklists, $productionLineId)) {
        echo json_encode(['error' => 'Failed to update production line checklist']);
        http_response_code(500);
        error_log('Failed to update production line checklist for production line ID: ' . $productionLineId);
        exit();
    }
}

if ($oldAndNewIds !== false) {
    echo json_encode(['success' => 'Production line updated successfully', 'data' => ['newAndOldIds' => $oldAndNewIds]]);
    exit();
}


echo json_encode(['error' => 'Failed to update production line']);
http_response_code(500);
exit();
