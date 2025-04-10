<?php

$gameSaveId = $_GET['id'];
$userId = $_GET['user'];

if (!$gameSaveId && !is_numeric($gameSaveId) && !$userId && !is_numeric($userId)) {
    header('Location: /admin/users');
    exit;
}

$gameSave = GameSaves::getSaveGameByIdAdmin($gameSaveId);

if (!$gameSave && !Users::getUserById($userId)) {
    header('Location: /admin/users');
    exit;
}

$productionLines = ProductionLines::getProductionLinesByGameSave($gameSaveId);

foreach ($productionLines as $productionLine) {
    $imports = ProductionLines::getImportsByProductionLine($productionLine->id);
    $production = ProductionLines::getProductionByProductionLine($productionLine->id);
    $powers = ProductionLines::getPowerByProductionLine($productionLine->id);
    $checklist = Checklist::getChecklist($productionLine->id);

    $productionLine->import_rows = count($imports);
    $productionLine->production_rows = count($production);
    $productionLine->power_rows = count($powers);
    $productionLine->checks = count($checklist);

}
?>
<div class="container">
    <div class="row align-items-center mb-3">
        <div class="col-lg-2"></div>
        <div class="col-lg-8">
            <h1 class="text-center">Production Lines for <b><?= htmlspecialchars($gameSave->title) ?></b></h1>
            <p class="text-center">Created at: <?= htmlspecialchars($gameSave->created_at) ?></p>
        </div>
        <div class="col-lg-2 text-lg-end text-center">
            <a href="/admin/users/game-saves?id=<?= $userId ?>" class="btn btn-primary">Return to game saves</a>
        </div>
    </div>

    <?= GlobalUtility::createTable(
                      $productionLines,
                      [
                          'name',
                          'power_consumbtion',
                          'import_rows',
                          'production_rows',
                          'power_rows',
                          'checks',
                          'updated_at',
                          'active',
                      ],
        excludeBools: [
                          'power_consumbtion',
                          'import_rows',
                          'production_rows',
                          'power_rows',
                          'checks',
                      ]
    ) ?>
    <p class="text-muted text-center">You can't edit, delete or add production lines here. Only the user can do
        that.</p>
</div>

