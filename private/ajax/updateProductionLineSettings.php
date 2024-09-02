<?php
if ($_POST) {
    $userId = $_SESSION['userId'];
    $productionLineId = $_POST['productionLineId'];
    $autoImportExport = $_POST['autoImportExport'] == 'true';
    $autoPowerMachine = $_POST['autoPowerMachine'] == 'true';
    $autoSave = $_POST['autoSave'] == 'true';

    ProductionLineSettings::updateProductionLineSettings($productionLineId, $userId, $autoImportExport, $autoPowerMachine, $autoSave);
} else {
    header('Location: /');
    exit();
}
