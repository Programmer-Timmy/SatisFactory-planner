<?php
ob_start();
$error = null;
$productLineId = $_GET['id'];
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
    var_dump($_POST);
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
            $productionData[] = (object)[
                'recipe_id' => $data['production_recipe_id'][$i],
                'product_quantity' => $data['production_quantity'][$i],
                'usage' => $data['production_usage'][$i],
                'export_amount_per_min' => $data['production_export'][$i]
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
        $error = 'Something went wrong';
    }
}elseif (isset($_POST['total_consumption'])) {
    $error = 'Please fill all the fields';
}
?>

<div class="px-5">
    <form method="post" onkeydown="return event.key != 'Enter';">
        <?php if ($error) : ?>
            <div class="alert alert-danger" role="alert">
                <?= $error ?>
            </div>
        <?php endif; ?>
        <div class="row justify-content-end align-items-center">
            <div class="col-md-3"></div>
            <div class="col-md-6 text-center">
                <h1><?= $productLine->title ?></h1>
            </div>
            <div class="col-md-3">
                <div class="text-md-end text-center">
                    <button type="submit" id="save_button" class="btn btn-primary"><i class="fa-solid fa-save"></i></button>
                    <button type="button" id="edit_product_line" class="btn btn-warning"><i class="fa-solid fa-pencil"></i></button>
                    <button type="button" id="showPower" class="btn btn-info"><i class="fa-solid fa-bolt"></i></button>
                    <a href="game_save?id=<?= $_SESSION['lastVisitedSaveGame'] ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i></a>
                </div>
            </div>
        </div>


        <div class="row">
            <div class="col-md-3">
                <h2>Imports</h2>
                <table class="table table-striped" id="imports">
                    <thead class="table-dark">
                    <tr>
                        <th scope="col">Name</th>
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
                            <select name="imports_item_id[]" step="any" class="form-control rounded-0 input-item-id"
                                    onchange="addInputRow('input-item-id')">
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
                <table class="table table-striped" id="recipes">
                    <thead class="table-dark">
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Quantity Per/min</th>
                        <th scope="col">Usage Per/min</th>
                        <th scope="col">Export Per/min</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($production as $product) : ?>
                    <?php var_dump(Recipes::getRecipeById($product->recipe_id)); ?>
                        <tr>
                            <td class="m-0 p-0">
                                <select name="production_recipe_id[]" class="form-control rounded-0 recipe" onchange="calculatePowerOfProduction(this)">
                                    <?php foreach ($Recipes as $recipe) : ?>
                                        <option <?php if ($product->recipe_id == $recipe->id) echo 'selected' ?>
                                                value="<?= $recipe->id ?>"><?= $recipe->name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="m-0 p-0">
                                <input min="0" type="number" name="production_quantity[]" step="any" required class="form-control rounded-0 production-quantity" onchange="calculatePowerOfProduction(this)"
                                       value="<?= $product->product_quantity ?>">
                            </td>
                            <td class="m-0 p-0">
                                <input min="0" type="number" name="production_usage[]" step="any" required class="form-control rounded-0 usage-amount" onchange="calculatePowerOfProduction(this)"
                                       value="<?= $product->local_usage ?>">
                            </td>
                            <td class="m-0 p-0">
                                <input min="0" type="number" name="production_export[]" step="any" required readonly class="form-control rounded-0 export-amount"
                                       value="<?= $product->export_amount_per_min ?>">
                            </td>

                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td class="m-0 p-0">
                            <select name="production_recipe_id[]" class="form-control rounded-0 item-recipe-id recipe" oninput="calculatePowerOfProduction(this)"
                                    onchange="addInputRow('item-recipe-id')">
                                <option value="" disabled selected>Select a recipe</option>
                                <?php foreach ($Recipes as $recipe) : ?>
                                    <option value="<?= $recipe->id ?>"><?= $recipe->name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="m-0 p-0">
                            <input min="0" type="number" step="any" name="production_quantity[]" value="0" required class="form-control rounded-0 production-quantity" onchange="calculatePowerOfProduction(this)">
                        </td>
                        <td class="m-0 p-0">
                            <input min="0" type="number" step="any" name="production_usage[]" value="0" required class="form-control rounded-0 usage-amount" onchange="calculatePowerOfProduction(this)"
                                   >
                        </td>
                        <td class="m-0 p-0">
                            <input min="0" type="number" step="any" name="production_export[]" value="0" required class="form-control rounded-0 export-amount">
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </div>
        <?php require_once '../private/views/Popups/showPower.php'; ?>
    </form>
</div>

<script src="js/tableFunctions.js"></script>
<script>
    calculateTotalConsumption();
</script>

<?php require_once '../private/views/Popups/editProductinoLine.php'; ?>
