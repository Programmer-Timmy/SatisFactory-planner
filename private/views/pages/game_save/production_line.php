<?php
require_once '../private/types/role.php';
global $changelog;

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

// Fetch all data for React to use
$imports = ProductionLines::getImportsByProductionLine($productLine->id);
$production = ProductionLines::getProductionByProductionLine($productLine->id);
$powers = ProductionLines::getPowerByProductionLine($productLine->id);
$checklist = Checklist::getChecklist($productLine->id);

$productionLineSettings = ProductionLineSettings::getProductionLineSettings($productLine->id);
if (!$productionLineSettings) {
    $productionLineSettings = ProductionLineSettings::addProductionLineSettings($productLine->id);
}

$items = Items::getAllItems();
$recipes = Recipes::getAllRecipes();
$buildings = Buildings::getAllBuildings();

// Build item class map for React
$itemClassMap = [];
foreach ($items as $item) {
    if (!empty($item->class_name)) {
        $itemClassMap[$item->id] = strtolower(str_replace('_', '-', $item->class_name));
    }
}

// Convert production data to JSON for React
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

?>

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

<!-- Power modal custom styles (isolated, do not change existing styles) -->
<style>
    /* Use isolated class names to avoid touching existing styles */
    .power-card {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 10px;
        padding: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        display: flex;
        align-items: flex-start;
        gap: 12px;
        position: relative; /* allow corner button positioning */
    }

    .power-card-body { display:flex; gap:12px; align-items:flex-start; width:100%; }

    .power-icon {
        width:44px; height:44px; object-fit:contain; border:1px solid var(--bs-border-color);
        border-radius:8px; background:var(--bs-tertiary-bg); padding:4px; flex:0 0 auto;
    }

    .power-fields { flex:1 1 auto; min-width:0; }

    .power-field-row { display:flex; gap:12px; align-items:center; flex-wrap:wrap; }

    .power-field { display:flex; flex-direction:column; gap:4px; min-width:0; }

    .power-label { font-size:11px; font-weight:600; color:var(--bs-secondary-color); }

    .power-input { min-width:120px; max-width:220px; }

    .power-meta { min-width:120px; text-align:right; }

    .power-actions { display:flex; align-items:flex-start; }

    /* Small screens: stack fields */
    @media (max-width:575.98px) {
        .power-field-row { flex-direction:column; align-items:stretch; }
        .power-meta { text-align:left; }
    }
</style>

<!-- React App Root -->
<div id="app-root"></div>

<!-- Initial Data for React Component -->
<script id="app-data" type="application/json">
<?= json_encode([
    'productLine' => $productLine,
    'imports' => $imports,
    'production' => $production,
    'powers' => $powers,
    'checklist' => $checklist,
    'items' => $items,
    'recipes' => $recipes,
    'buildings' => $buildings,
    'itemClassMap' => $itemClassMap,
    'productionSettings' => $jsonArray,
    'viewOnly' => $viewOnly,
    'firstProduction' => $firstProduction,
    'userId' => $_SESSION['userId'] ?? null,
    'importsReadonly' => $viewOnly || (bool)$productionLineSettings->auto_import_export,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?>
</script>

<!-- Load React App -->
<script src="/js/productionLines.js?v=<?= $changelog['version'] ?>"></script>

<?php
if (DedicatedServer::getBySaveGameId($gameSaveId) && GameSaves::checkAccess($gameSaveId, $_SESSION['userId'], Permission::SERVER_VIEW)): ?>
    <script src="/js/dedicatedServer.js?v=<?= $changelog['version'] ?>"></script>
    <script>
        new DedicatedServer(<?=$gameSaveId ?>);
    </script>
<?php endif; ?>


<script>
    // Make data accessible to React component
    window.appData = JSON.parse(document.getElementById('app-data').textContent);
</script>
