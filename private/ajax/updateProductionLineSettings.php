<?php
if ($_POST) {
    $userId = $_SESSION['userId'];

    if (empty($productLine) || !ProductionLines::checkProductionLineVisability($productLine->game_saves_id, $userId)) {
        header('Location: /');
        exit();
    }

    $productionLineId = $_POST['productionLineId'];
    $autoImportExport = $_POST['autoImportExport'] == 'true';
    $autoPowerMachine = $_POST['autoPowerMachine'] == 'true';
    $autoSave = $_POST['autoSave'] == 'true';

    ProductionLineSettings::updateProductionLineSettings($productionLineId, $userId, $autoImportExport, $autoPowerMachine, $autoSave);
} else {
    header('Location: /');
    exit();
}
