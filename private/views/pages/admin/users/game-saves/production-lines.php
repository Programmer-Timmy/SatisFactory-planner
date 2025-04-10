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

?>
<div class="container">
    <div class="row align-items-center mb-3">
        <div class="col-lg-2"></div>
        <div class="col-lg-8">
            <h1 class="text-center">Production Lines for <b><?= htmlspecialchars($gameSave->title)?></b></h1>
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
            'updated_at',
            'active'
        ],
    ) ?>
    <p class="text-muted text-center">You can't edit, delete or add production lines here. Only the user can do that.</p>
</div>

