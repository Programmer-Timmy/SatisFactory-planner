<?php
$productLineId = $_GET['id'];
$productLine = ProductionLines::getProductionLineById($productLineId);

if (empty($productLine) || !ProductionLines::checkProductionLineVisability($productLine->game_saves_id, $_SESSION['userId'])) {
    header('Location: game_save?id=' . $_SESSION['lastVisitedSaveGame']);
    exit();
}

$imports = ProductionLines::getImportsByProductionLine($productLine->id);
$production = ProductionLines::getProductionByProductionLine($productLine->id);
$power = ProductionLines::getPowerByProductionLine($productLine->id);

$items = Items::getAllItems();
$Recipes = Recipes::getAllRecipes();
$buildings = Buildings::getAllBuildings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    }
}
?>

<div class="mt-5 px-5">
    <h1 class="text-center pb-3"><?= $productLine->title ?></h1>
    <form method="post">
        <div class="row">
            <div class="col-md-2">
                <h2>Imports</h2>
                <table class="table table-striped" id="imports">
                    <thead class="table-dark">
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Quantity</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($imports)) : ?>

                    <?php endif; ?>
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
                                <input min="0" type="number" name="imports_ammount[]" class="form-control rounded-0"
                                       value="<?= $import->ammount ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td class="m-0 p-0 w-75">
                            <select name="imports_item_id[]" class="form-control rounded-0 input-item-id"
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
            <div class="col-md-6">
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
                                <input min="0" type="number" name="production_quantity[]" required class="form-control rounded-0 production-quantity" onchange="calculatePowerOfProduction(this)"
                                       value="<?= $product->product_quantity ?>">
                            </td>
                            <td class="m-0 p-0">
                                <input min="0" type="number" name="production_usage[]" required class="form-control rounded-0 usage-amount" onchange="calculatePowerOfProduction(this)"
                                       value="<?= $product->local_usage ?>">
                            </td>
                            <td class="m-0 p-0">
                                <input min="0" type="number" name="production_export[]" required readonly class="form-control rounded-0 export-amount"
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
                            <input min="0" type="number" name="production_quantity[]" value="0" required class="form-control rounded-0 production-quantity" onchange="calculatePowerOfProduction(this)">
                        </td>
                        <td class="m-0 p-0">
                            <input min="0" type="number" name="production_usage[]" value="0" required class="form-control rounded-0 usage-amount" onchange="calculatePowerOfProduction(this)"
                                   >
                        </td>
                        <td class="m-0 p-0">
                            <input min="0" type="number" name="production_export[]" value="0" required class="form-control rounded-0 export-amount">
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-4">
                <h2>Power</h2>
                <table class="table table-striped">
                    <thead class="table-dark">
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Amount</th>
                        <th scope="col">Clock Speed</th>
                        <th scope="col">Consumption</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($power as $power) : ?>
                        <tr <?=$power->user ? 'class="user"' : '' ?>>
                            <td class="m-0 p-0 w-50">
                                <select name="power_building_id[]" class="form-control rounded-0 building"
                                        onchange="calculateConsumption(this)">
                                    <?php foreach ($buildings as $building) : ?>
                                        <option <?php if ($power->buildings_id == $building->id) echo 'selected' ?>
                                                value="<?= $building->id ?>"><?= $building->name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="m-0 p-0 w-25">
                                <input min="0" type="number" name="power_amount[]"
                                       class="form-control rounded-0 quantity " onchange="calculateConsumption(this)"
                                       value="<?= $power->building_ammount ?>">
                            </td>
                            <td class="m-0 p-0 w-25">
                                <input min="0" type="number" name="power_clock_speed[]" step="any" class="form-control rounded-0 clock-speed"
                                       onchange="calculateConsumption(this)"
                                       value="<?= $power->clock_speed ?>">
                            </td>
                            <td class="w-25 m-0 p-0">
                                <input type="number" name="power_Consumption[]"
                                       class="form-control rounded-0 consumption" disabled onchange="calculateTotalConsumption()"
                                       value="<?= $power->building_ammount * $power->power_used * ($power->clock_speed / 100) ?>">
                                <input type="hidden" class="user" name="user[]" value="<?=$power->user?>">
                            </td>

                        </tr>
                    <?php endforeach; ?>
                    <tr class="user">
                        <td class="m-0 p-0 w-50 ">
                            <select name="power_building_id[]" class="form-control rounded-0 building-id building"
                                    onchange="addInputRow('building-id')" oninput="calculateConsumption(this)">
                                <option value="" disabled selected>Select a building</option>
                                <?php foreach ($buildings as $building) : ?>
                                    <option value="<?= $building->id ?>"><?= $building->name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="m-0 p-0 w-25">
                            <input min="0" type="number" name="power_amount[]" class="form-control rounded-0 quantity"
                                   onchange="calculateConsumption(this)">
                        </td>
                        <td class="m-0 p-0 w-25">
                            <input min="0" max="250" type="number" name="power_clock_speed[]"
                                   class="form-control rounded-0 clock-speed" step="any" value="100"
                                   onchange="calculateConsumption(this)">
                        </td>
                        <td class="w-25 m-0 p-0">
                            <input type="number" name="power_Consumption[]" disabled class="form-control rounded-0 consumption"
                                   onchange="calculateTotalConsumption(this)">
                            <input type="hidden" class="user" name="user[]" value="1">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="1" class="table-dark">
                            Total:
                        </td>
                        <td colspan="2"></td>
                        <td class="w-25 m-0 p-0">
                            <input type="number" name="total_consumption" readonly class="form-control rounded-0"
                                   id="totalConsumption" value="<?= $productLine->power_consumbtion ?>">
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
    </form>
</div>

<script src="js/tableFunctions.js"></script>
<script>
    calculateTotalConsumption();
</script>
