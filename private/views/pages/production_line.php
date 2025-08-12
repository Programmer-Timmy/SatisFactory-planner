<?php
ob_start();
$error = null;
$productLineId = $_GET['id'];
if ($productLineId == null) {
    header('Location: game_save?id=' . $_SESSION['lastVisitedSaveGame']);
    exit();
}

$productLine = ProductionLines::getProductionLineById($productLineId);

if (empty($productLine) || !ProductionLines::checkProductionLineVisability($productLine->game_saves_id, $_SESSION['userId'])) {
    header('Location: game_save?id=' . $_SESSION['lastVisitedSaveGame']);
    exit();
}

$firstProduction = Users::checkIfFirstProduction($_SESSION['userId']);

$imports = ProductionLines::getImportsByProductionLine($productLine->id);
$production = ProductionLines::getProductionByProductionLine($productLine->id);
$powers = ProductionLines::getPowerByProductionLine($productLine->id);
$checklist = Checklist::getChecklist($productLine->id);

$items = Items::getAllItems();
$recipes = Recipes::getAllRecipes();
$buildings = Buildings::getAllBuildings();

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
?>

<script id="settings-data" type="application/json">
<?= json_encode($jsonArray, JSON_PRETTY_PRINT) ?>
</script>


<style>
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
<input type="hidden" id="gameSaveId" value="<?= $productLine->game_saves_id ?>">

<div class="px-3 px-lg-5">
    <form method="post" onkeydown="return event.key != 'Enter';">
        <?php if ($error) : ?>
            <div class="alert alert-danger text-center" role="alert">
                <i class="fa-solid fa-exclamation-triangle"></i> <?= $error ?>
            </div>
        <?php endif; ?>
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
                    <a href="game_save?id=<?= $_SESSION['lastVisitedSaveGame'] ?>" class="btn btn-secondary mb-1"
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
                <h2>Imports</h2>
                <table class="table table-striped" id="imports">
                    <thead class="table-dark">
                    <tr>
                        <th scope="col">Item</th>
                        <th scope="col">Quantity</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($imports as $import) : ?>
                        <tr>
                            <td class="m-0 p-0 w-75">
                                <select name="imports_item_id[]" class="form-control rounded-0">
                                    <?php foreach ($items as $item) : ?>
                                        <option <?php if ($import->items_id == $item->id) echo 'selected' ?>
                                                value="<?= $item->id ?>"><?= $item->name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="m-0 p-0 w-25">
                                <input min="0" type="number" step="any" name="imports_ammount[]"
                                       class="form-control rounded-0"
                                       value="<?= $import->ammount ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td class="m-0 p-0 w-75">
                            <select name="imports_item_id[]" step="any"
                                    class="form-control rounded-0 input-item-id">
                                <option value="" disabled selected>Select an item</option>
                                <?php foreach ($items as $item) : ?>
                                    <option value="<?= $item->id ?>"><?= $item->name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="m-0 p-0 w-25">
                            <input min="0" type="number" name="imports_ammount[]" class="form-control rounded-0">
                        </td>
                    </tr>

                    </tbody>

                </table>
            </div>
            <div class="col-md-9">
                <h2>Production</h2>
                <div style="position: relative; overflow-x: auto; overflow-y: visible;">
                    <table class="table table-striped " id="recipes">
                        <thead class="table-dark">
                        <tr>
                            <th scope="col">Recipe</th>
                            <th scope="col">Quantity Per/min</th>
                            <th scope="col">Product</th>
                            <th scope="col">Local Usage Per/min</th>
                            <th scope="col">Export Per/min</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($production as $product) : ?>
                            <tr>
                                <td class="hidden">
                                    <input type="hidden" name="production_id[]" value="<?= $product->id ?>">
                                </td>
                                <td class="m-0 p-0 position-relative" <?php if ($product->item_name_2) echo 'rowspan="2"' ?>>
                                    <i class="fa-solid fa-gear open-p-settings position-absolute link-primary text-muted z-1"
                                       style="font-size: 11px; top:2px; left:2px;"></i>
                                    <?= GlobalUtility::generateRecipeSelect($recipes, $product->recipe_id) ?>
                                </td>
                                <td class="m-0 p-0" <?php if ($product->item_name_2) echo 'rowspan="2"' ?>>
                                    <input min="0" type="number" name="production_quantity[]"
                                           step="any" <?php if ($product->item_name_2) echo 'style="height: 78px"' ?>
                                           required class="form-control rounded-0 production-quantity" "
                                    value="<?= $product->product_quantity ?>">
                                </td>
                                <td class="m-0 p-0">
                                    <input type="text" readonly class="form-control rounded-0 product-name"
                                           value="<?= $product->item_name_1 ?>">
                                </td>
                                <td class="m-0 p-0">
                                    <input min="0" type="number" name="production_usage[]" step="any" required
                                           readonly
                                           class="form-control rounded-0 usage-amount"
                                           value="<?= $product->local_usage ?>">
                                </td>
                                <td class="m-0 p-0">
                                    <input min="0" type="number" name="production_export[]" step="any" required
                                           readonly class="form-control rounded-0 export-amount"
                                           value="<?= $product->export_amount_per_min ?>">
                                </td>
                            </tr>
                            <?php if ($product->item_name_2) : ?>

                                <tr class="extra-output">
                                    <td class="m-0 p-0">
                                        <input type="text" readonly class="form-control rounded-0 product-name"
                                               value="<?= $product->item_name_2 ?>">
                                    </td>
                                    <td class="m-0 p-0">
                                        <input min="0" type="number" name="production_usage2[]" step="any" required
                                               readonly
                                               class="form-control rounded-0 usage-amount" "
                                        value="<?= $product->local_usage2 ?>">
                                    </td>
                                    <td class="m-0 p-0">
                                        <input min="0" type="number" name="production_export2[]" step="any" required
                                               readonly class="form-control rounded-0 export-amount"
                                               value="<?= $product->export_ammount_per_min2 ?>">
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <tr>
                            <td class="hidden">
                                <input type="hidden" name="production_id[]" value="<?= generateUUID() ?>">
                            </td>
                            <td class="m-0 p-0 position-relative">
                                <i class="fa-solid fa-gear open-p-settings position-absolute link-primary text-muted"
                                   style="font-size: 11px; top:2px; left:2px;"></i>
                                <select name="production_recipe_id[]"
                                        class="form-control rounded-0 item-recipe-id recipe">
                                    <option value="" disabled selected>Select a recipe</option>
                                    <?php foreach ($recipes as $recipe) : ?>
                                        <option value="<?= $recipe->id ?>"><?= $recipe->name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="m-0 p-0">
                                <input min="0" type="number" step="any" name="production_quantity[]" value="0"
                                       required class="form-control rounded-0 production-quantity">
                            </td>
                            <td class="m-0 p-0">
                                <input type="text" readonly class="form-control rounded-0 product-name">
                            </td>
                            <td class="m-0 p-0">
                                <input min="0" type="number" step="any" name="production_usage[]" value="0" required
                                       readonly class="form-control rounded-0 usage-amount">
                            </td>
                            <td class="m-0 p-0">
                                <input min="0" type="number" step="any" name="production_export[]" value="0"
                                       required
                                       readonly class="form-control rounded-0 export-amount">
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php require_once '../private/views/Popups/productionLine/showPower.php'; ?>
    </form>
</div>
<!--    custom select-->
<!-- Make sure you have Font Awesome included -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">





<script>
    // should search based on the produced product name
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.querySelector('input[name="recipeSearch"]');
        const items = document.querySelectorAll('.select-items > div');

        // when in the search input uncollapse the select-items div
        searchInput.addEventListener('focus', function () {
            const selectItems = searchInput.closest('.bg-white').querySelector('.select-items');
            // move the classlist to the body and position it to the richt location
            selectItems.classList.add('show');

            // if it does not fit the screen move it to the top
            const maxHeight = 300;
            const rect = selectItems.getBoundingClientRect();

// Check if dropdown bottom (top + maxHeight) fits in viewport
            if (rect.top + maxHeight > window.innerHeight) {
                selectItems.style.top = 'auto';
                selectItems.style.bottom = '100%';
            } else {
                selectItems.style.top = '';
                selectItems.style.bottom = '';
            }

        });

        // when the search input loses focus, collapse the select-items div
        searchInput.addEventListener('blur', function () {
            const selectItems = searchInput.closest('.bg-white').querySelector('.select-items');
            setTimeout(() => {
                selectItems.classList.remove('show');

                // reset the position of the select-items div
                selectItems.style.top = '';
                selectItems.style.bottom = '';
            }, 200); // Delay to allow click events to register
        });

        searchInput.addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();
            items.forEach(item => {
                const productNames = item.querySelectorAll('input[name="productName"]')
                const recipeName = item.querySelector('h6');
                const matches = Array.from(productNames).some(input => input.value.toLowerCase().includes(searchTerm)) ||
                    recipeName.textContent.toLowerCase().includes(searchTerm);
                item.style.display = matches ? 'block' : 'none';
            });

            // if no items are visible, show a message
            const visibleItems = Array.from(items).filter(item => item.style.display !== 'none');
            const selectItems = searchInput.closest('.bg-white').querySelector('.select-items');
            if (visibleItems.length === 0) {
                if (!selectItems.querySelector('.text-center.text-muted')) {
                    selectItems.insertAdjacentHTML('beforeend', `<div class="text-center text-muted">No recipes found...</div>`);
                }
            } else if (selectItems.querySelector('.text-center.text-muted')) {
                selectItems.querySelector('.text-center.text-muted').remove();
            }
        });

        document.querySelectorAll('.select-items > div').forEach(item => {
            item.addEventListener('click', function () {
                const recipeId = this.getAttribute('data-recipe-id');
                const productName = this.querySelector('h6').textContent;
                const input = document.querySelector('input[name="recipeId"]');
                input.value = recipeId;
                searchInput.value = productName;
                const selectItems = searchInput.closest('.bg-white').querySelector('.select-items');
                selectItems.classList.remove('show');
            });
        });

    //     on load active a search
        const activeSearch = document.querySelector('input[name="recipeSearch"]');
        if (activeSearch) {
            activeSearch.dispatchEvent(new Event('input'));
        }
    });
</script>

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
if (DedicatedServer::getBySaveGameId($_SESSION['lastVisitedSaveGame'])) : ?>
    <script src="js/dedicatedServer.js"></script>
    <script>
        new DedicatedServer(<?= $_SESSION['lastVisitedSaveGame'] ?>);
    </script>
<?php endif; ?>
<script type="" src="js/tables.js?v=<?= $changelog['version'] ?>"></script>
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


