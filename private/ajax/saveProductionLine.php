<?php

if (!isset($_POST['data']) || !isset($_POST['id'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

if (empty($productLine) || !ProductionLines::checkProductionLineVisability($productLine->game_saves_id, $_SESSION['userId'])) {
    header('Location: /');
    exit();
}

$data = json_decode($_POST['data'], true);

$importsData = [];
$productionData = [];
$powerData = [];
$totalPower = 0;

$productionLineId = $_POST['id'];

$importRows = $data['importTable']['tableRows'];
for ($i = 0; $i < count($data['importTable']['tableRows']); $i++) {
    if ($importRows[$i]['cells'][0] == null || $importRows[$i]['cells'][1] == '') {
        continue;
    }
    $importsData[] = (object)[
        'id' => $importRows[$i]['cells'][0],
        'ammount' => $importRows[$i]['cells'][1]
    ];
}

$productionRows = $data['productionTable']['tableRows'];
for ($i = 0; $i < count($data['productionTable']['tableRows']); $i++) {
    if ($productionRows[$i]['cells'][0] == null || $productionRows[$i]['cells'][1] == 0 || $productionRows[$i]['cells'][1] == '') {
        continue;
    }
    $secondUsage = $productionRows[$i]['doubleExport'] == 'true' ? $productionRows[$i]['extraCells'][1] : null;
    $secondExport = $productionRows[$i]['doubleExport'] == 'true' ? $productionRows[$i]['extraCells'][2] : null;
    $productionData[] = (object)[
        'recipe_id' => $productionRows[$i]['cells'][0],
        'product_quantity' => $productionRows[$i]['cells'][1],
        'usage' => $productionRows[$i]['cells'][3],
        'export_amount_per_min' => $productionRows[$i]['cells'][4],
        'local_usage2' => $secondUsage,
        'export_ammount_per_min2' => $secondExport
    ];
}

$powerRows = $data['powerTable']['tableRows'];
for ($i = 0; $i < count($data['powerTable']['tableRows']); $i++) {
    if ($powerRows[$i]['cells'][0] == null || $powerRows[$i]['cells'][3] == '') {
        continue;
    }

    $powerData[] = (object)[
        'buildings_id' => $powerRows[$i]['cells'][0],
        'building_ammount' => $powerRows[$i]['cells'][1],
        'clock_speed' => $powerRows[$i]['cells'][2],
        'power_used' => $powerRows[$i]['cells'][3],
        'user' => $powerRows[$i]['cells'][4]
    ];
    $totalPower += $powerRows[$i]['cells'][3];
}

if (ProductionLines::saveProductionLine($importsData, $productionData, $powerData, $totalPower, $productionLineId)) {
    echo json_encode(['success' => 'Production line updated successfully']);
    exit();
}

echo json_encode(['error' => 'Failed to update production line']);
