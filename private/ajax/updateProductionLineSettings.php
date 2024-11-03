<?php
if ($_POST) {

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

    $productLine = ProductionLines::getProductionLineById($_POST['productionLineId']);

    if (empty($productLine) || !ProductionLines::checkProductionLineVisability($productLine->game_saves_id, $_SESSION['userId'])) {
        echo json_encode(['error' => 'You do not have access to this production line.']);
        exit();
    }

    $productionLineId = $_POST['productionLineId'];
    $autoImportExport = $_POST['autoImportExport'] == 'true';
    $autoPowerMachine = $_POST['autoPowerMachine'] == 'true';
    $autoSave = $_POST['autoSave'] == 'true';

    ProductionLineSettings::updateProductionLineSettings($productionLineId, $autoImportExport, $autoPowerMachine, $autoSave);
    echo json_encode(['success' => 'Production line settings updated.']);
} else {
    echo json_encode(['error' => 'No data was sent.']);
    exit();
}
