<?php
require_once '../private/types/role.php';
global $productLine;
global $gameSaveId;
global $viewOnly;
global $firstProduction;
global $imports;
global $production;
global $powers;
global $checklist;
global $items;
global $recipes;
global $buildings;

$error = null;
$productLineId = $_GET['id'] ?? null;
$gameSaveId = $_GET['game_save_id'] ?? null;

if ($productLineId === null && $gameSaveId !== null) {
    header('Location: /game_save/'. $gameSaveId);
    exit();
} elseif ($gameSaveId == null) {
    header('Location: /game_save/'. $_SESSION['lastVisitedSaveGame']);
    exit();
}

$productLine = ProductionLines::getProductionLineById($productLineId, $gameSaveId);

$viewOnly = GameSaves::checkAccess($productLine->game_saves_id, $_SESSION['userId'], Permission::SAVEGAME_EDIT, negate: true);

if ($viewOnly === null) {
    header('Location: game_save?id=' . $_SESSION['lastVisitedSaveGame']);
    exit();
} elseif ($viewOnly) {
    $_SESSION['info'] = 'You can only view this production line.';
}

$firstProduction = Users::checkIfFirstProduction($_SESSION['userId']);

$imports = ProductionLines::getImportsByProductionLine($productLine->id);
$production = ProductionLines::getProductionByProductionLine($productLine->id);
$powers = ProductionLines::getPowerByProductionLine($productLine->id);
$checklist = Checklist::getChecklist($productLine->id);

$productionLineSettings = ProductionLineSettings::getProductionLineSettings($productLine->id);
if (!$productionLineSettings) {
    $productionLineSettings = ProductionLineSettings::addProductionLineSettings($productLine->id);
}
$importsReadonly = $viewOnly || (bool)$productionLineSettings->auto_import_export;

$items = Items::getAllItems();
$recipes = Recipes::getAllRecipes();
$buildings = Buildings::getAllBuildings();

$itemClassMap = [];
foreach ($items as $item) {
    if (!empty($item->class_name)) {
        $itemClassMap[$item->id] = strtolower(str_replace('_', '-', $item->class_name));
    }
}

global $changelog;

function generateUUID(): string {
    $data = random_bytes(16);

    // Versie instellen (4) en variant instellen (RFC 4122)
    $data[6] = chr(ord($data[6]) & 0x0f | 0x30); // Versie 4
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant RFC 4122

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

$jsonArray = [];
foreach ($production as $product) {
    if ($product->clock_speed === null) {
        $product->clock_speed = 100;
    }
    if ($product->use_somersloop === null) {
        $product->use_somersloop = 0;
    }

    $jsonArray[] = [
        'id' => $product->id,
        'clockSpeed' => (int)$product->clock_speed,
        'useSomersloop' => $product->use_somersloop == 1
    ];
}

function trimDecimal(string $value): string {
    // Convert to float to remove unnecessary decimal zeros
    if (strpos($value, '.') !== false) {
        // Remove trailing zeros after decimal
        $value = rtrim(rtrim($value, '0'), '.');
    }

    // If the value becomes empty (e.g., "0.0"), return '0'
    return $value === '' ? '0' : $value;
}

?>

<script id="settings-data" type="application/json">
<?= json_encode($jsonArray, JSON_PRETTY_PRINT) ?>
</script>

<script id="items-class-map" type="application/json"><?= json_encode($itemClassMap) ?></script>

<style>
    .pl-field-with-icon {
        display: flex;
        align-items: center;
        gap: 8px;
        padding-left: 8px;
        padding-right: 8px;
        min-width: 0;
    }

    .pl-field-with-icon > .form-control,
    .pl-field-with-icon > .pl-value {
        flex: 1 1 auto;
        min-width: 0;
    }

    .pl-field-with-icon > .pl-value {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .pl-readonly-select:disabled {
        opacity: 1;
        color: var(--bs-body-color);
        background: var(--bs-tertiary-bg);
        border-color: var(--bs-border-color);
        cursor: default;
    }

    .pl-item-icon,
    .pl-building-icon {
        width: 22px;
        height: 22px;
        object-fit: contain;
        flex: 0 0 auto;
        filter: drop-shadow(0 0 0 rgba(0, 0, 0, 0));
    }

    .pl-building-icon {
        opacity: 0.9;
    }

    /* Component lists (no <table> UI) */
    .pl-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .pl-add-recipe-card {
        border: 1px dashed var(--bs-border-color);
        border-radius: 10px;
        padding: 18px 10px;
        background: var(--bs-body-bg);
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .pl-add-recipe-card .pl-add-recipe-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 999px;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-tertiary-bg);
        color: var(--bs-body-color);
    }

    .pl-add-recipe-card .pl-add-recipe-btn:hover {
        background: rgba(var(--bs-primary-rgb), 0.08);
        border-color: rgba(var(--bs-primary-rgb), 0.35);
    }

    .pl-row {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 10px;
        padding: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        position: relative;
    }

    .pl-production-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .pl-production-row .pl-row-main {
        padding-left: 0; /* collapse toggle is absolute; don't reserve layout space */
    }

    /* Collapse button: absolute, tucked in the top-right next to delete */
    .pl-collapse-toggle {
        position: absolute;
        top: -13px; /* center on card border */
        right: 19px; /* aligns next to delete, also centered on border */
        left: auto;
        width: 26px;
        height: 26px;
        padding: 0;
        border: 1px solid var(--bs-border-color);
        border-radius: 999px;
        background: var(--bs-body-bg);
        color: var(--bs-secondary-color);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        box-shadow: none;
        z-index: 2;
    }

    .pl-collapse-toggle i {
        font-size: 12px;
    }

    .pl-collapse-toggle:hover {
        background: rgba(var(--bs-secondary-rgb), 0.10);
        border-color: rgba(var(--bs-secondary-rgb), 0.35);
        color: var(--bs-body-color);
    }

    .pl-production-row.is-collapsed {
        padding: 6px 8px;
    }

    .pl-production-row.is-collapsed .pl-row-details {
        display: none;
    }

    /* Collapsed cards (Option B): replace big header with a single compact bar */
    .pl-production-row.is-collapsed .pl-row-grid {
        grid-template-columns: 1fr;
    }

    .pl-production-row.is-collapsed .pl-row-side {
        display: none;
    }

    .pl-production-row.is-collapsed .pl-header-main {
        display: none;
    }

    .pl-collapsed-summary {
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        min-width: 0;
    }

    .pl-production-row.is-collapsed .pl-collapsed-summary {
        display: flex;
        padding-right: 56px; /* space for absolute collapse + delete buttons */
    }

    .pl-collapsed-recipe {
        font-size: 12px;
        font-weight: 600;
        line-height: 1.1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .pl-collapsed-right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 0 0 auto;
    }

    .pl-collapsed-qty {
        font-size: 11px;
        color: var(--bs-secondary-color);
        white-space: nowrap;
    }

    .pl-collapsed-products {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: nowrap;
    }

    .pl-collapsed-output {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        min-width: 0;
    }

    .pl-collapsed-byproduct {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        min-width: 0;
    }

    .pl-collapsed-rate {
        font-size: 10px;
        color: var(--bs-secondary-color);
        white-space: nowrap;
        line-height: 1;
    }

    .pl-collapsed-summary .pl-item-icon {
        width: 18px;
        height: 18px;
        border: 1px solid var(--bs-border-color);
        border-radius: 6px;
        background: var(--bs-tertiary-bg);
        padding: 2px;
        object-fit: contain;
    }

    .pl-field {
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 0;
    }

    .pl-label {
        font-size: 11px;
        font-weight: 600;
        color: var(--bs-secondary-color);
        line-height: 1.1;
    }

    .pl-value {
        padding: 0.375rem 0.5rem;
        border: 1px solid var(--bs-border-color);
        border-radius: 6px;
        background: var(--bs-tertiary-bg);
        line-height: 1.2;
    }

    .pl-number {
        font-variant-numeric: tabular-nums;
    }

    /* Production card: 2-column layout (main flow + small settings column) */
    .pl-row-grid {
        display: grid;
        grid-template-columns: 1fr 220px;
        gap: 12px;
        align-items: start;
    }

    /* Imports still use the simple header layout */
    .pl-row-header {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 130px;
        gap: 10px;
        align-items: start;
    }

    .pl-import-row {
        position: relative;
    }

    .pl-import-collapse-toggle {
        position: absolute;
        top: -13px; /* center on card border */
        right: -13px; /* center on card border */
        width: 26px;
        height: 26px;
        padding: 0;
        border: 1px solid var(--bs-border-color);
        border-radius: 999px;
        background: var(--bs-body-bg);
        color: var(--bs-secondary-color);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        z-index: 2;
    }

    .pl-import-collapse-toggle i {
        font-size: 12px;
    }

    .pl-import-row.is-collapsed .pl-import-details {
        display: none;
    }

    .pl-import-row:not(.is-collapsed) .pl-import-collapsed {
        display: none;
    }

    .pl-import-collapsed {
        display: flex;
        align-items: center;
        gap: 10px;
        padding-right: 34px; /* reserve space for the corner button */
    }

    .pl-import-collapsed-main {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .pl-import-collapsed-name {
        font-size: 12px;
        font-weight: 600;
        line-height: 1.1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .pl-import-collapsed-qty {
        font-size: 12px;
        line-height: 1.1;
    }

    .pl-header-main {
        display: grid;
        grid-template-columns: 1fr 160px;
        gap: 8px;
        align-items: start;
        min-width: 0;
    }

    .pl-machine-settings {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        justify-content: flex-start;
        padding-right: 0;
    }

    .pl-row-side {
        display: flex;
        flex-direction: column;
        gap: 8px;
        align-items: stretch;
    }

    /* Delete button sits inside the card corner so it feels "attached" */
    .pl-side-actions {
        position: absolute;
        top: -13px; /* center on card border */
        right: -13px; /* center on card border */
        display: flex;
        justify-content: flex-end;
        z-index: 2;
    }

    .pl-side-actions .delete-production-row {
        width: 26px;
        height: 26px;
        padding: 0;
        border: 1px solid var(--bs-border-color);
        border-radius: 999px;
        background: var(--bs-body-bg);
        color: var(--bs-secondary-color);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        box-shadow: none;
        transition: background-color 120ms ease, color 120ms ease, border-color 120ms ease;
    }

    .pl-side-actions .delete-production-row i {
        font-size: 12px;
    }

    .pl-side-actions .delete-production-row:hover {
        background: rgba(var(--bs-danger-rgb), 0.10);
        border-color: rgba(var(--bs-danger-rgb), 0.35);
        color: var(--bs-danger);
    }

    .pl-side-actions .delete-production-row:focus {
        box-shadow: 0 0 0 0.15rem rgba(var(--bs-danger-rgb), 0.25);
    }

    .pl-building-stack {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 3px;
        margin-top: 15px; /* align with the Clock% input (below its label) */
        flex: 0 0 auto;
    }

    .pl-building-amount {
        font-size: 12px;
        font-weight: 600;
        color: var(--bs-secondary-color);
        line-height: 1;
    }

    .pl-info-icon {
        display: inline-flex;
        align-items: center;
        cursor: help;
        opacity: 0.75;
        line-height: 1;
        vertical-align: middle;
    }

    .pl-machine-settings .pl-building-icon {
        width: 46px;
        height: 46px;
        border: 1px solid var(--bs-border-color);
        border-radius: 10px;
        background: var(--bs-tertiary-bg);
        padding: 4px;
        object-fit: contain;
    }

    .pl-machine-controls {
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 140px;
    }

    .pl-header-main .form-control,
    .pl-header-main .recipe-select .search-input {
        padding-top: 0.25rem;
        padding-bottom: 0.25rem;
        font-size: 0.9rem;
        height: 34px;
    }

    .pl-clock-speed {
        padding-top: 0.25rem;
        padding-bottom: 0.25rem;
        font-size: 0.9rem;
    }

    .pl-row-flow {
        margin-top: 8px;
        display: grid;
        grid-template-columns: 1.2fr 1fr 1fr;
        gap: 8px;
        align-items: start;
    }

    .pl-extra-output .pl-row-flow {
        margin-top: 0;
    }

    .pl-actions {
        display: flex;
        align-items: stretch;
        justify-content: flex-end;
    }

    .pl-actions .delete-production-row {
        width: 44px;
        padding-left: 0;
        padding-right: 0;
    }

    /* Settings are inline now; no gear/context-menu trigger */

    .pl-extra-output {
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px dashed var(--bs-border-color);
        display: none;
    }

    .pl-extra-output.is-visible {
        display: block;
    }

    .pl-flow-arrow {
        font-size: 12px;
    }

    @media (max-width: 991.98px) {
        .pl-row-header {
            grid-template-columns: 1fr;
        }

        .pl-row-grid {
            grid-template-columns: 1fr;
        }

        .pl-row-flow {
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .pl-row-side {
            align-items: flex-start;
        }
    }

    /* Chrome, Safari, Edge, Opera */
    /* Chrome, Safari, Edge, Opera */
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Firefox */
    input[type=number] {
        -moz-appearance: textfield;
    }

    .form-control:focus {
        box-shadow: none;
        border-color: #4a4a4a;
    }

    input:read-only {
        cursor: default;
    }
</style>
<input type="hidden" id="dataVersion" value="<?= SiteSettings::getDataVersion() ?>">
<input type="hidden" id="gameSaveId" value="<?= $gameSaveId ?>">
<input type="hidden" id="productionLineId" value="<?= $productLine->id ?>">
<input type="hidden" id="viewOnly" value="<?= $viewOnly ?>"> <!-- i mean you can change it but ye you get errors :) -->

<div class="px-3 px-lg-5">
    <form method="post" onkeydown="return event.key != 'Enter';">
        <?php GlobalUtility::displayFlashMessages() ?>
        <div class="alert alert-success d-none fade" role="alert" id="saveSuccessAlert"></div>
        <div class="alert alert-danger d-none fade" role="alert" id="saveErrorAlert"></div>
        <input type="hidden" name="total_consumption" id="total_consumption">
        <div class="row justify-content-end align-items-center">
            <div class="col-lg-3"></div>
            <div class="col-lg-6 text-center">
                <h1 id="productionLineName">Production Line - <?= $productLine->title ?></h1>
            </div>
            <div class="col-lg-3">
                <div class="text-lg-end text-center">
                    <button type="submit" id="save_button" class="btn btn-primary mb-1 disabled"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top" data-bs-html="true"
                            data-bs-title="Save production line.<br> <small>Hold <b>Shift</b> to save without returning to the save game.</small>">
                        <i class="fa-solid fa-save"></i></button>
                    <button type="button" id="edit_product_line" class="btn btn-warning mb-1 disabled"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top" data-bs-title="Edit the production line"><i
                                class="fa-solid fa-pencil"></i></button>
                    <button type="button" id="showPower" class="btn btn-info mb-1 disabled" data-bs-toggle="tooltip"
                            data-bs-placement="top" data-bs-title="Show power consumption"><i
                                class="fa-solid fa-bolt"></i></button>
                    <button type="button" id="showVisualizationButton" class="btn btn-info mb-1 disabled"
                            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Show visualization"><i
                                class="fa-solid fa-project-diagram"></i></button>
                    <button type="button" id="showCheckList" class="btn btn-info mb-1 disabled" data-bs-toggle="tooltip"
                            data-bs-placement="top" data-bs-title="Checklist"><i class="fa-solid fa-list-check"></i>
                    </button>
                    <button type="button" id="showHelp" class="btn btn-info mb-1" data-bs-toggle="tooltip"
                            data-bs-placement="top" data-bs-title="Need help? Click here!"><i
                                class="fa-regular fa-question-circle"></i></button>
                    <a href="/game_save/<?= $gameSaveId ?>/" class="btn btn-secondary mb-1"
                       data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Back to game save"><i
                                class="fa-solid fa-arrow-left"></i></a>
                </div>
            </div>
        </div>
        <div class="mt-auto position-absolute top-50 start-50 translate-middle-x w-100" id="loading">
            <div class="d-flex justify-content-center flex-column align-items-center">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="progress mt-3 w-75 mx-auto">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" id="loading-progress"
                         role="progressbar" style="width: 0%"
                         aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%
                    </div>
                </div>
            </div>

        </div>
        <div class="row d-none" id="main-content">
            <div class="col-md-3">
                <h2 class="mb-0">Imports</h2>
                <p class="text-muted small mb-2">Auto-calculated imports update as you edit recipes (toggle Auto Import-Export in Edit).</p>

                <div id="imports" class="pl-list">
                    <?php $importIndex = 0; ?>
                    <?php foreach ($imports as $import) : ?>
                        <div class="pl-row pl-import-row<?= $importsReadonly ? ' is-collapsed' : '' ?>" data-row-index="<?= $importIndex ?>">
                            <?php
                            $importIcon = !empty($import->item_class_name) ? strtolower(str_replace('_', '-', $import->item_class_name)) : null;
                            $importQtyCollapsed = trimDecimal((string)$import->ammount);
                            ?>
                            <?php if (!$importsReadonly): ?>
                                <button type="button" class="btn btn-sm pl-import-collapse-toggle" aria-label="Collapse/expand import" aria-expanded="true">
                                    <i class="fa-solid fa-chevron-up"></i>
                                </button>
                            <?php endif; ?>

                            <div class="pl-import-collapsed" aria-hidden="true">
                                <img class="pl-item-icon" data-role="import-icon-collapsed" loading="lazy"
                                     <?php if ($importIcon): ?>src="/image/items/<?= $importIcon ?>_256.png"<?php else: ?>style="display:none"<?php endif; ?>
                                     alt="">
                                <div class="pl-import-collapsed-main">
                                    <div class="pl-import-collapsed-name" data-role="import-name-collapsed"></div>
                                    <div class="pl-import-collapsed-qty">
                                        <span class="pl-number" data-role="import-qty-collapsed"><?= $importQtyCollapsed ?></span>
                                        <span class="text-muted">/min</span>
                                    </div>
                                </div>
                            </div>

                            <div class="pl-import-details">
                                <div class="pl-row-header">
                                    <div class="pl-field">
                                        <div class="pl-label">Item</div>
                                        <div class="pl-field-with-icon">
                                            <img class="pl-item-icon" data-role="import-icon" loading="lazy"
                                                 <?php if ($importIcon): ?>src="/image/items/<?= $importIcon ?>_256.png"<?php else: ?>style="display:none"<?php endif; ?>
                                                 alt="">
                                            <?php if ($importsReadonly): ?>
                                                <input type="number" hidden name="imports_item_id[]" data-field="itemId" value="<?= $import->items_id ?>">
                                                <select class="form-control rounded-0 pl-readonly-select" disabled aria-disabled="true" tabindex="-1" data-sp-skip="true" data-field="itemId">
                                                    <?php foreach ($items as $item) : ?>
                                                        <option <?php if ($import->items_id == $item->id) echo 'selected' ?>
                                                                value="<?= $item->id ?>"><?= $item->name ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <select name="imports_item_id[]" class="form-control rounded-0" data-field="itemId">
                                                    <?php foreach ($items as $item) : ?>
                                                        <option <?php if ($import->items_id == $item->id) echo 'selected' ?>
                                                                value="<?= $item->id ?>"><?= $item->name ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="pl-field">
                                        <div class="pl-label">Qty / min</div>
                                        <?php if ($importsReadonly): ?>
                                            <input type="number" hidden step="any" name="imports_ammount[]" data-field="quantity" value="<?= $importQtyCollapsed ?>">
                                            <div class="pl-value pl-number" data-role="import-qty-display"><?= $importQtyCollapsed ?></div>
                                        <?php else: ?>
                                            <input min="0" type="number" step="any" name="imports_ammount[]"
                                                   class="form-control rounded-0" data-field="quantity"
                                                   value="<?= $import->ammount ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php $importIndex++; ?>
                    <?php endforeach; ?>

                    <?php if (!$importsReadonly): ?>
                        <!-- Empty import row to allow quick adding -->
                        <div class="pl-row pl-import-row" data-row-index="<?= $importIndex ?>">
                            <button type="button" class="btn btn-sm pl-import-collapse-toggle" aria-label="Collapse/expand import" aria-expanded="true">
                                <i class="fa-solid fa-chevron-up"></i>
                            </button>

                            <div class="pl-import-collapsed" aria-hidden="true">
                                <img class="pl-item-icon" data-role="import-icon-collapsed" loading="lazy" style="display:none" alt="">
                                <div class="pl-import-collapsed-main">
                                    <div class="pl-import-collapsed-name" data-role="import-name-collapsed"></div>
                                    <div class="pl-import-collapsed-qty">
                                        <span class="pl-number" data-role="import-qty-collapsed">0</span>
                                        <span class="text-muted">/min</span>
                                    </div>
                                </div>
                            </div>

                            <div class="pl-import-details">
                                <div class="pl-row-header">
                                    <div class="pl-field">
                                        <div class="pl-label">Item</div>
                                        <div class="pl-field-with-icon">
                                            <img class="pl-item-icon" data-role="import-icon" loading="lazy" style="display:none" alt="">
                                            <select name="imports_item_id[]" class="form-control rounded-0" data-field="itemId">
                                                <?php foreach ($items as $item) : ?>
                                                    <option value="<?= $item->id ?>"><?= $item->name ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="pl-field">
                                        <div class="pl-label">Qty / min</div>
                                        <input min="0" type="number" step="any" name="imports_ammount[]" value="0"
                                               class="form-control rounded-0" data-field="quantity">
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-9">
                <div class="pl-production-header mb-1">
                    <h2 class="mb-0">Production</h2>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="pl-toggle-collapse-all"
                            data-state="expanded"
                            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
                            data-bs-title="Collapse or expand all recipe cards.">
                        <i class="fa-solid fa-compress me-1"></i>
                        <span data-role="label">Collapse all</span>
                    </button>
                </div>
                <p class="text-muted small mb-2">Flow: pick Recipe → set Qty/min → see output, usage and export. Read-only values are shown as labels (not inputs).</p>

                <div id="recipes" class="pl-list">
                    <?php $prodIndex = 0; ?>
                    <?php foreach ($production as $product) : ?>
                        <div class="pl-row pl-production-row" data-row-index="<?= $prodIndex ?>">
                            <button type="button" class="btn btn-sm pl-collapse-toggle" aria-label="Collapse/expand recipe details" data-role="collapse-toggle">
                                <i class="fa-solid fa-chevron-up"></i>
                            </button>
                            <input type="hidden" name="production_id[]" value="<?= $product->id ?>">

                            <?php
                            $buildingIcon = !empty($product->building_class_name)
                                ? strtolower(str_ireplace('build', 'desc', str_replace('_', '-', $product->building_class_name)))
                                : null;
                            ?>

                            <div class="pl-row-grid">
                                <div class="pl-row-main">
                                    <div class="pl-header-main">
                                        <div class="pl-field">
                                            <div class="pl-label">Recipe</div>
                                            <?= GlobalUtility::generateRecipeSelect($recipes, $product->recipe_id) ?>
                                        </div>

                                        <div class="pl-field">
                                            <div class="pl-label">Qty / min</div>
                                            <input min="0" type="text" name="production_quantity[]" step="any" required
                                                   class="form-control rounded-0 production-quantity" data-field="quantity"
                                                   value="<?= trimDecimal($product->product_quantity) ?>">
                                        </div>
                                    </div>

                                    <?php
                                    $collapsedOut1Icon = !empty($product->item_class_name_1) ? strtolower(str_replace('_', '-', $product->item_class_name_1)) : null;
                                    $collapsedOut2Icon = !empty($product->item_class_name_2) ? strtolower(str_replace('_', '-', $product->item_class_name_2)) : null;
                                    ?>
                                    <div class="pl-collapsed-summary" aria-hidden="true">
                                        <div class="pl-collapsed-recipe" data-role="collapsed-recipe-name" title="Select recipe">Select recipe</div>
                                        <div class="pl-collapsed-right">
                                            <div class="pl-collapsed-products">
                                                <span class="pl-collapsed-output" data-role="collapsed-output1-wrap" <?php if (!$collapsedOut1Icon): ?>style="display:none"<?php endif; ?>>
                                                    <img class="pl-item-icon" data-role="collapsed-output1" loading="lazy"
                                                         <?php if ($collapsedOut1Icon): ?>src="/image/items/<?= $collapsedOut1Icon ?>_256.png"<?php else: ?>style="display:none"<?php endif; ?>
                                                         alt="">
                                                    <span class="pl-collapsed-rate" data-role="collapsed-output1-rate"><?= trimDecimal($product->product_quantity) ?>/min</span>
                                                </span>

                                                <span class="pl-collapsed-output pl-collapsed-byproduct" data-role="collapsed-byproduct" <?php if (!$collapsedOut2Icon): ?>style="display:none"<?php endif; ?>>
                                                    <img class="pl-item-icon" data-role="collapsed-output2" loading="lazy"
                                                         <?php if ($collapsedOut2Icon): ?>src="/image/items/<?= $collapsedOut2Icon ?>_256.png"<?php else: ?>style="display:none"<?php endif; ?>
                                                         alt="">
                                                    <span class="pl-collapsed-rate" data-role="collapsed-output2-rate">0/min</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pl-row-details">
                                        <div class="pl-row-flow">
                                            <div class="pl-field">
                                                <div class="pl-label"><i class="fa-solid fa-arrow-right text-muted me-1 pl-flow-arrow"></i>Output</div>
                                                <div class="pl-field-with-icon">
                                                    <?php
                                                    $out1Icon = !empty($product->item_class_name_1) ? strtolower(str_replace('_', '-', $product->item_class_name_1)) : null;
                                                    ?>
                                                    <img class="pl-item-icon" data-role="output1" loading="lazy"
                                                         <?php if ($out1Icon): ?>src="/image/items/<?= $out1Icon ?>_256.png"<?php else: ?>style="display:none"<?php endif; ?>
                                                         alt="">
                                                    <input type="hidden" class="product-name" value="<?= htmlspecialchars($product->item_name_1) ?>">
                                                    <div class="pl-value" data-role="product1-text"><?= $product->item_name_1 ?></div>
                                                </div>
                                            </div>

                                            <div class="pl-field">
                                                <div class="pl-label">
                                                    Local usage / min
                                                    <span class="ms-1 pl-info-icon"
                                                          data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
                                                          data-bs-title="Amount consumed locally by other recipes in this production line.">
                                                        <i class="fa-regular fa-circle-question"></i>
                                                    </span>
                                                </div>
                                                <input type="hidden" class="usage-amount" value="<?= trimDecimal($product->local_usage) ?>">
                                                <div class="pl-value pl-number" data-role="usage1-text"><?= trimDecimal($product->local_usage) ?></div>
                                            </div>

                                            <div class="pl-field">
                                                <div class="pl-label">
                                                    Export / min
                                                    <span class="ms-1 pl-info-icon"
                                                          data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
                                                          data-bs-title="Amount available to export to other production lines (Qty − Local usage).">
                                                        <i class="fa-regular fa-circle-question"></i>
                                                    </span>
                                                </div>
                                                <input type="hidden" class="export-amount" value="<?= trimDecimal($product->export_amount_per_min) ?>">
                                                <div class="pl-value pl-number" data-role="export1-text"><?= trimDecimal($product->export_amount_per_min) ?></div>
                                            </div>
                                        </div>

                                        <div class="pl-extra-output extra-output <?php if ($product->item_name_2) echo 'is-visible' ?>">
                                            <div class="pl-row-flow">
                                                <div class="pl-field">
                                                    <div class="pl-label"><i class="fa-solid fa-arrow-right text-muted me-1 pl-flow-arrow"></i>By-product</div>
                                                    <div class="pl-field-with-icon">
                                                        <?php
                                                        $out2Icon = !empty($product->item_class_name_2) ? strtolower(str_replace('_', '-', $product->item_class_name_2)) : null;
                                                        ?>
                                                        <img class="pl-item-icon" data-role="output2" loading="lazy"
                                                             <?php if ($out2Icon): ?>src="/image/items/<?= $out2Icon ?>_256.png"<?php else: ?>style="display:none"<?php endif; ?>
                                                             alt="">
                                                        <input type="hidden" data-sp-skip="true" class="product-name" value="<?= htmlspecialchars($product->item_name_2 ?? '') ?>">
                                                        <div class="pl-value" data-role="product2-text"><?= $product->item_name_2 ?></div>
                                                    </div>
                                                </div>

                                                <div class="pl-field">
                                                    <div class="pl-label">
                                                        Local usage / min
                                                        <span class="ms-1 pl-info-icon"
                                                              data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
                                                              data-bs-title="Amount consumed locally by other recipes in this production line.">
                                                            <i class="fa-regular fa-circle-question"></i>
                                                        </span>
                                                    </div>
                                                    <input type="hidden" data-sp-skip="true" class="usage-amount" value="<?= trimDecimal($product->local_usage2 ?? '0') ?>">
                                                    <div class="pl-value pl-number" data-role="usage2-text"><?= trimDecimal($product->local_usage2 ?? '0') ?></div>
                                                </div>

                                                <div class="pl-field">
                                                    <div class="pl-label">
                                                        Export / min
                                                        <span class="ms-1 pl-info-icon"
                                                              data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
                                                              data-bs-title="Amount available to export to other production lines (Qty − Local usage).">
                                                            <i class="fa-regular fa-circle-question"></i>
                                                        </span>
                                                    </div>
                                                    <input type="hidden" data-sp-skip="true" class="export-amount" value="<?= trimDecimal($product->export_ammount_per_min2 ?? '0') ?>">
                                                    <div class="pl-value pl-number" data-role="export2-text"><?= trimDecimal($product->export_ammount_per_min2 ?? '0') ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="pl-side-actions">
                                    <button type="button" class="btn btn-sm delete-production-row" data-id="<?= $product->id ?>">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>

                                <div class="pl-row-side">
                                    <div class="pl-machine-settings">
                                        <div class="pl-building-stack">
                                            <img class="pl-building-icon" data-role="building" loading="lazy"
                                                 <?php if ($buildingIcon): ?>src="/image/items/<?= $buildingIcon ?>_256.png"<?php else: ?>style="display:none"<?php endif; ?>
                                                 alt="">
                                            <div class="pl-building-amount pl-number" data-role="building-amount"></div>
                                        </div>

                                        <div class="pl-machine-controls">
                                            <div class="pl-field">
                                                <div class="pl-label">Clock %</div>
                                                <input type="number" min="0" max="250" step="any"
                                                       class="form-control rounded-0 pl-clock-speed"
                                                       data-sp-skip="true"
                                                       value="<?= (int)$product->clock_speed ?>">
                                            </div>

                                            <div class="form-check mt-1">
                                                <input class="form-check-input pl-use-somersloop" type="checkbox"
                                                       data-sp-skip="true" <?= ((int)$product->use_somersloop) === 1 ? 'checked' : '' ?>>
                                                <label class="form-check-label small">Somersloop</label>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <?php $prodIndex++; ?>
                    <?php endforeach; ?>

                    <?php if (!$viewOnly): ?>
                        <div class="pl-add-recipe-card" data-role="add-recipe-card">
                            <button type="button" class="pl-add-recipe-btn" id="pl-add-recipe"
                                    data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
                                    data-bs-title="Add a new recipe row">
                                <i class="fa-solid fa-plus"></i>
                                <span>Add recipe</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- Recipe template row (hidden) -->
                    <div class="pl-row pl-production-row pl-recipe-template d-none" data-role="recipe-template" data-row-index="0">
                        <button type="button" class="btn btn-sm pl-collapse-toggle" aria-label="Collapse/expand recipe details" data-role="collapse-toggle">
                            <i class="fa-solid fa-chevron-up"></i>
                        </button>
                        <input type="hidden" name="production_id[]" value="<?= generateUUID() ?>">

                        <div class="pl-row-grid">
                            <div class="pl-row-main">
                                <div class="pl-header-main">
                                    <div class="pl-field">
                                        <div class="pl-label">Recipe</div>
                                        <?= GlobalUtility::generateRecipeSelect($recipes) ?>
                                    </div>

                                    <div class="pl-field">
                                        <div class="pl-label">Qty / min</div>
                                        <input min="0" type="text" step="any" name="production_quantity[]" value="0" required
                                               class="form-control rounded-0 production-quantity" data-field="quantity">
                                    </div>
                                </div>

                                <div class="pl-collapsed-summary" aria-hidden="true">
                                    <div class="pl-collapsed-recipe" data-role="collapsed-recipe-name" title="Select recipe">Select recipe</div>
                                    <div class="pl-collapsed-right">
                                        <div class="pl-collapsed-products">
                                            <span class="pl-collapsed-output" data-role="collapsed-output1-wrap" style="display:none">
                                                <img class="pl-item-icon" data-role="collapsed-output1" loading="lazy" style="display:none" alt="">
                                                <span class="pl-collapsed-rate" data-role="collapsed-output1-rate">0/min</span>
                                            </span>
                                            <span class="pl-collapsed-output pl-collapsed-byproduct" data-role="collapsed-byproduct" style="display:none">
                                                <img class="pl-item-icon" data-role="collapsed-output2" loading="lazy" style="display:none" alt="">
                                                <span class="pl-collapsed-rate" data-role="collapsed-output2-rate">0/min</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="pl-row-details">
                                    <div class="pl-row-flow">
                                        <div class="pl-field">
                                            <div class="pl-label"><i class="fa-solid fa-arrow-right text-muted me-1 pl-flow-arrow"></i>Output</div>
                                            <div class="pl-field-with-icon">
                                                <img class="pl-item-icon" data-role="output1" loading="lazy" style="display:none" alt="">
                                                <input type="hidden" class="product-name" value="">
                                                <div class="pl-value" data-role="product1-text"></div>
                                            </div>
                                        </div>

                                        <div class="pl-field">
                                            <div class="pl-label">
                                                Local usage / min
                                                <span class="ms-1 pl-info-icon"
                                                      data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
                                                      data-bs-title="Amount consumed locally by other recipes in this production line.">
                                                    <i class="fa-regular fa-circle-question"></i>
                                                </span>
                                            </div>
                                            <input type="hidden" class="usage-amount" value="0">
                                            <div class="pl-value pl-number" data-role="usage1-text">0</div>
                                        </div>

                                        <div class="pl-field">
                                            <div class="pl-label">
                                                Export / min
                                                <span class="ms-1 pl-info-icon"
                                                      data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
                                                      data-bs-title="Amount available to export to other production lines (Qty − Local usage).">
                                                    <i class="fa-regular fa-circle-question"></i>
                                                </span>
                                            </div>
                                            <input type="hidden" class="export-amount" value="0">
                                            <div class="pl-value pl-number" data-role="export1-text">0</div>
                                        </div>
                                    </div>

                                    <div class="pl-extra-output extra-output">
                                        <!-- hidden unless a double-output recipe is selected -->
                                        <div class="pl-row-flow">
                                            <div class="pl-field">
                                                <div class="pl-label"><i class="fa-solid fa-arrow-right text-muted me-1 pl-flow-arrow"></i>By-product</div>
                                                <div class="pl-field-with-icon">
                                                    <img class="pl-item-icon" data-role="output2" loading="lazy" style="display:none" alt="">
                                                    <input type="hidden" data-sp-skip="true" class="product-name" value="">
                                                    <div class="pl-value" data-role="product2-text"></div>
                                                </div>
                                            </div>
                                            <div class="pl-field">
                                                <div class="pl-label">
                                                    Local usage / min
                                                    <span class="ms-1 pl-info-icon"
                                                          data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
                                                          data-bs-title="Amount consumed locally by other recipes in this production line.">
                                                        <i class="fa-regular fa-circle-question"></i>
                                                    </span>
                                                </div>
                                                <input type="hidden" data-sp-skip="true" class="usage-amount" value="0">
                                                <div class="pl-value pl-number" data-role="usage2-text">0</div>
                                            </div>
                                            <div class="pl-field">
                                                <div class="pl-label">
                                                    Export / min
                                                    <span class="ms-1 pl-info-icon"
                                                          data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
                                                          data-bs-title="Amount available to export to other production lines (Qty − Local usage).">
                                                        <i class="fa-regular fa-circle-question"></i>
                                                    </span>
                                                </div>
                                                <input type="hidden" data-sp-skip="true" class="export-amount" value="0">
                                                <div class="pl-value pl-number" data-role="export2-text">0</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pl-side-actions">
                                <button type="button" class="btn btn-sm delete-production-row">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>

                            <div class="pl-row-side">
                                <div class="pl-machine-settings">
                                    <div class="pl-building-stack">
                                        <img class="pl-building-icon" data-role="building" loading="lazy" style="display:none" alt="">
                                        <div class="pl-building-amount pl-number" data-role="building-amount"></div>
                                    </div>

                                    <div class="pl-machine-controls">
                                        <div class="pl-field">
                                            <div class="pl-label">Clock %</div>
                                            <input type="number" min="0" max="250" step="any"
                                                   class="form-control rounded-0 pl-clock-speed"
                                                   data-sp-skip="true"
                                                   value="100">
                                        </div>

                                        <div class="form-check mt-1">
                                            <input class="form-check-input pl-use-somersloop" type="checkbox" data-sp-skip="true">
                                            <label class="form-check-label small">Somersloop</label>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php require_once '../private/views/Popups/productionLine/showPower.php'; ?>
    </form>
</div>
<!--    custom select-->
<!-- Make sure you have Font Awesome included -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<div class="offcanvas offcanvas-end" data-bs-scroll="true" data-bs-backdrop="false" tabindex="-1" id="Checklist"
     aria-labelledby="offcanvasChecklist">
    <div class="offcanvas-header pb-1">
        <h5 class="offcanvas-title" id="offcanvasChecklist">Checklist</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="input-group p-3 pt-0">
        <input type="search" class="form-control mt-2" id="searchChecklist" placeholder="Search">
        <button class="btn btn-primary mt-2" id="resetSearchChecklist"><i class="fa-solid fa-undo"></i></button>
    </div>
    <div class="offcanvas-body overflow-y-auto">
        <?php if (!empty($checklist)) : ?>
            <data class="hidden" id="checkListData">
                <?= json_encode($checklist) ?>
            </data>
        <?php endif ?>
    </div>
</div>


<?php
if (DedicatedServer::getBySaveGameId($productLine->game_saves_id) && GameSaves::checkAccess($productLine->game_saves_id, $_SESSION['userId'], Permission::SERVER_VIEW)) : ?>
    <script src="/js/dedicatedServer.js"></script>
    <script>
        new DedicatedServer(<?= $productLine->game_saves_id ?>);
    </script>
<?php endif; ?>
<script type="" src="/js/tables.js?v=<?= @filemtime(__DIR__ . '/../../../../public/js/tables.js') ?: ($changelog['version'] ?? '0') ?>"></script>
<?php require_once '../private/views/Popups/productionLine/editProductinoLine.php'; ?>

<!-- Help Modal -->
<?php require_once '../private/views/Popups/productionLine/helpProductionLine.php'; ?>

<?php require_once '../private/views/Popups/productionLine/showVisualization.php'; ?>

<?php if ($firstProduction) : ?>
    <script>
        jQuery(function () {
            const popupModal = new bootstrap.Modal(document.getElementById('helpModal'));
            popupModal.show();
        });
    </script>
<?php endif; ?>

<script>
    <!--    open off canvas-->
    var offcanvasChecklist = new bootstrap.Offcanvas(document.getElementById('Checklist'));
    document.getElementById('showCheckList').addEventListener('click', function () {
        offcanvasChecklist.show();
    });
</script>


