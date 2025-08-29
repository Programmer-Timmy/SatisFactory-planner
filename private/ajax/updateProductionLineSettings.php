<?php
if ($_POST) {

    if (!isset($_POST['gameSaveId'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No game save id provided']);
        exit();
    }

    $productionLineId = $_POST['productionLineId'];


    if (!ProductionLines::validateAccess($_POST['gameSaveId'], $productionLineId,  $_SESSION['userId'])) {
        http_response_code(403);
        echo json_encode(['error' => 'You do not have permission to edit this production line']);
        exit();
    }

    $productionLineId = ProductionLines::getProductionLineById($_POST['productionLineId']);

    if (empty($productLine) || !ProductionLines::checkProductionLineVisability($productLine->game_saves_id, $productionLineId, $_SESSION['userId'])) {
        echo json_encode(['error' => 'You do not have access to this production line.']);
        exit();
    }

    $autoImportExport = $_POST['autoImportExport'] == 'true';
    $autoPowerMachine = $_POST['autoPowerMachine'] == 'true';
    $autoSave = $_POST['autoSave'] == 'true';

    ProductionLineSettings::updateProductionLineSettings($productionLineId, $autoImportExport, $autoPowerMachine, $autoSave);
    echo json_encode(['success' => 'Production line settings updated.']);
} else {
    echo json_encode(['error' => 'No data was sent.']);
    exit();
}
