<?php
if ($_POST) {
    $userId = $_SESSION['userId'];
    $productLine = ProductionLines::getProductionLineById($_POST['productionLineId']);

    if (empty($productLine) || !ProductionLines::checkProductionLineVisability($productLine->game_saves_id, $userId)) {
        echo json_encode(['error' => 'You do not have access to this production line.']);
        exit();
    }

    $productionLineId = $_POST['productionLineId'];
    $autoImportExport = $_POST['autoImportExport'] == 'true';
    $autoPowerMachine = $_POST['autoPowerMachine'] == 'true';
    $autoSave = $_POST['autoSave'] == 'true';

    ProductionLineSettings::updateProductionLineSettings($productionLineId, $userId, $autoImportExport, $autoPowerMachine, $autoSave);
    echo json_encode(['success' => 'Production line settings updated.']);
} else {
    echo json_encode(['error' => 'No data was sent.']);
    exit();
}
