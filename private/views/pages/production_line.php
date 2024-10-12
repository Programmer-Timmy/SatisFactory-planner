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

$imports = ProductionLines::getImportsByProductionLine($productLine->id);
$production = ProductionLines::getProductionByProductionLine($productLine->id);
$powers = ProductionLines::getPowerByProductionLine($productLine->id);

$items = Items::getAllItems();
$Recipes = Recipes::getAllRecipes();
$buildings = Buildings::getAllBuildings();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['total_consumption'])) {
    $data = $_POST;
    $total_consumption = $_POST['total_consumption'];
    $importsData = [];
    $productionData = [];
    $powerData = [];

    if (!empty($data['imports_item_id'])) {
        for ($i = 0; $i < count($data['imports_item_id']); $i++) {
            if ($data['imports_ammount'][$i] == 0 || $data['imports_ammount'][$i] == '' || !$data['imports_item_id'][$i]) {
                continue;
            }
            $importsData[] = (object)[
                'id' => $data['imports_item_id'][$i],
                'ammount' => $data['imports_ammount'][$i]
            ];
        }
    }


    if (!empty($data['production_recipe_id'])) {
        for ($i = 0; $i < count($data['production_recipe_id']); $i++) {
            if ($data['production_quantity'][$i] == 0 || $data['production_quantity'][$i] == '' || !$data['production_recipe_id'][$i]) {
                continue;
            }
            $secondUsage = 0;
            $secondExport = 0;
            if (Recipes::checkIfMultiOutput($data['production_recipe_id'][$i])) {
                $secondUsage = $data['production_usage2'][0];
                // remove the just saved value from the array
                array_shift($data['production_usage2']);
                $secondExport = $data['production_export2'][0];
                array_shift($data['production_export2']);
            }

            $productionData[] = (object)[
                'recipe_id' => $data['production_recipe_id'][$i],
                'product_quantity' => $data['production_quantity'][$i],
                'usage' => $data['production_usage'][$i],
                'export_amount_per_min' => $data['production_export'][$i],
                'local_usage2' => $secondUsage,
                'export_ammount_per_min2' => $secondExport

            ];

        }
    }

    if (!empty($data['power_building_id'])) {
        for ($i = 0; $i < count($data['power_building_id']); $i++) {
            if ($data['power_amount'][$i] == 0 || $data['power_amount'][$i] == '' || !$data['power_building_id'][$i]) {
                continue;
            }

            $powerData[] = (object)[
                'buildings_id' => $data['power_building_id'][$i],
                'building_ammount' => $data['power_amount'][$i],
                'clock_speed' => $data['power_clock_speed'][$i],
                'power_used' => $buildings[array_search($data['power_building_id'][$i], array_column($buildings, 'id'))]->power_used,
                'user' => $data['user'][$i]
            ];
        }
    }

    if (ProductionLines::saveProductionLine($importsData, $productionData, $powerData, $total_consumption, $productLine->id)) {
        header('Location: game_save?id=' . $_SESSION['lastVisitedSaveGame']);
        exit();
    }else{
        $error = 'Something went wrong while saving the production line. Please try again. If the problem persists, please contact the administrator.';
    }
}elseif (isset($_POST['total_consumption'])) {
    $error = 'Please fill all the fields';
}

$changelog = json_decode(file_get_contents('changelog.json'), true)[0];

?>

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

    .form-control:focus{
        box-shadow: none;
        border-color: #4a4a4a;
    }

    input:read-only {
        cursor: default;
    }

</style>

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
            <div class="col-md-3"></div>
            <div class="col-md-6 text-center">
                <h1 id="productionLineName">Production Line - <?= $productLine->title ?></h1>
            </div>
            <div class="col-md-3">
                <div class="text-md-end text-center">
                    <button type="submit" id="save_button" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" data-bs-title="Save production line.<br> <small>Hold <b>Shift</b> to save without returning to the save game.</small>"><i class="fa-solid fa-save"></i></button>
                    <button type="button" id="edit_product_line" class="btn btn-warning" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Edit the production line"><i class="fa-solid fa-pencil"></i></button>
                    <button type="button" id="showPower" class="btn btn-info" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Show power consumption"><i class="fa-solid fa-bolt"></i></button>
                    <a href="game_save?id=<?= $_SESSION['lastVisitedSaveGame'] ?>" class="btn btn-secondary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Back to game save"><i class="fa-solid fa-arrow-left"></i></a>
                </div>
            </div>
        </div>
        <div class="row">
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
                                <input min="0" type="number" step="any" name="imports_ammount[]" class="form-control rounded-0"
                                       value="<?= $import->ammount ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td class="m-0 p-0 w-75">
                            <select name="imports_item_id[]" step="any" class="form-control rounded-0 input-item-id">
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
                <div class="overflow-x-auto">
                <table class="table table-striped " id="recipes">
                    <thead class="table-dark">
                    <tr>
                        <th scope="col">Recipe</th>
                        <th scope="col">Quantity Per/min</th>
                        <th scope="col">Product</th>
                        <th scope="col">Usage Per/min</th>
                        <th scope="col">Export Per/min</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($production as $product) : ?>
                        <tr>
                            <td class="m-0 p-0" <?php if ($product->item_name_2) echo 'rowspan="2"' ?>>

                                <select name="production_recipe_id[]" class="form-control rounded-0 recipe"<?php if ($product->item_name_2) echo 'style="height: 78px"'?>>
                                <?php foreach ($Recipes as $recipe) : ?>
                                        <option <?php if ($product->recipe_id == $recipe->id) echo 'selected' ?>
                                                value="<?= $recipe->id ?>"><?= $recipe->name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="m-0 p-0" <?php if ($product->item_name_2) echo 'rowspan="2"' ?>>
                                <input min="0"  type="number" name="production_quantity[]" step="any" <?php if ($product->item_name_2) echo 'style="height: 78px"'?> required class="form-control rounded-0 production-quantity" "
                                       value="<?= $product->product_quantity ?>">
                            </td>
                                                       <td class="m-0 p-0">
                                <input type="text"  readonly class="form-control rounded-0 product-name"
                                       value="<?= $product->item_name_1 ?>">
                            </td>
                            <td class="m-0 p-0">
                                <input min="0" type="number" name="production_usage[]" step="any" required readonly
                                       class="form-control rounded-0 usage-amount"
                                       value="<?= $product->local_usage ?>">
                            </td>
                            <td class="m-0 p-0">
                                <input min="0" type="number" name="production_export[]" step="any" required readonly class="form-control rounded-0 export-amount"
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
                                <input min="0" type="number" name="production_usage2[]" step="any" required readonly
                                       class="form-control rounded-0 usage-amount" "
                                       value="<?= $product->local_usage2 ?>">
                            </td>
                            <td class="m-0 p-0">
                                <input min="0" type="number" name="production_export2[]" step="any" required readonly class="form-control rounded-0 export-amount"
                                       value="<?= $product->export_ammount_per_min2 ?>">
                            </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <tr>
                        <td class="m-0 p-0">
                            <select name="production_recipe_id[]" class="form-control rounded-0 item-recipe-id recipe">
                                <option value="" disabled selected>Select a recipe</option>
                                <?php foreach ($Recipes as $recipe) : ?>
                                    <option value="<?= $recipe->id ?>"><?= $recipe->name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="m-0 p-0">
                            <input min="0" type="number" step="any" name="production_quantity[]" value="0" required class="form-control rounded-0 production-quantity">
                        </td>
                        <td class="m-0 p-0">
                            <input type="text" readonly class="form-control rounded-0 product-name">
                        </td>
                        <td class="m-0 p-0">
                            <input min="0" type="number" step="any" name="production_usage[]" value="0" required
                                   readonly class="form-control rounded-0 usage-amount">
                        </td>
                        <td class="m-0 p-0">
                            <input min="0" type="number" step="any" name="production_export[]" value="0" required
                                   readonly class="form-control rounded-0 export-amount">
                        </td>
                    </tr>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
        <?php require_once '../private/views/Popups/showPower.php'; ?>
    </form>
</div>
<?php
if (DedicatedServer::getBySaveGameId($_SESSION['lastVisitedSaveGame'])) : ?>
    <script src="js/dedicatedServer.js"></script>
    <script>
        new DedicatedServer(<?= $_SESSION['lastVisitedSaveGame'] ?>);
    </script>
<?php endif; ?>
<script type="module" src="js/tables.js?v=<?= $changelog['version'] ?>"></script>
<?php require_once '../private/views/Popups/editProductinoLine.php'; ?>

