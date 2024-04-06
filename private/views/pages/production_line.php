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

    for ($i = 0; $i < count($data['imports_item_id']); $i++) {
        if ($data['imports_ammount'][$i] == 0 || $data['imports_ammount'][$i] == '' || !$data['imports_item_id'][$i]) {
            continue;
        }
        $importsData[] = (object)[
            'id' => $data['imports_item_id'][$i],
            'ammount' => $data['imports_ammount'][$i]
        ];
    }

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

    for ($i = 0; $i < count($data['power_building_id']); $i++) {
        if ($data['power_amount'][$i] == 0 || $data['power_amount'][$i] == '' || !$data['power_building_id'][$i]) {
            continue;
        }
        $powerData[] = (object)[
            'buildings_id' => $data['power_building_id'][$i],
            'building_ammount' => $data['power_amount'][$i],
            'clock_speed' => $data['power_clock_speed'][$i],
            'power_used' => $buildings[array_search($data['power_building_id'][$i], array_column($buildings, 'id'))]->power_used
        ];
    }

    if(ProductionLines::saveProductionLine($importsData, $productionData, $powerData, $total_consumption, $productLine->id)){
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
                            <select name="production_recipe_id[]" class="form-control rounded-0">
                                <?php foreach ($Recipes as $recipe) : ?>
                                    <option <?php if ($product->recipe_id == $recipe->id) echo 'selected' ?>
                                            value="<?= $recipe->id ?>"><?= $recipe->name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="m-0 p-0">
                            <input min="0" type="number" name="production_quantity[]" class="form-control rounded-0"
                                   value="<?= $product->product_quantity ?>">
                        </td>
                        <td class="m-0 p-0">
                            <input min="0" type="number" name="production_usage[]" class="form-control rounded-0"
                                   value="<?= $product->usage ?>">
                        </td>
                        <td class="m-0 p-0">
                            <input min="0" type="number" name="production_export[]" class="form-control rounded-0"
                                   value="<?= $product->export_amount_per_min ?>">
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td class="m-0 p-0">
                        <select name="production_recipe_id[]" class="form-control rounded-0 item-recipe-id"
                                onchange="addInputRow('item-recipe-id')">
                            <?php foreach ($Recipes as $recipe) : ?>
                                <option value="<?= $recipe->id ?>"><?= $recipe->name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="m-0 p-0">
                        <input min="0" type="number" name="production_quantity[]" class="form-control rounded-0" value="">
                    </td>
                    <td class="m-0 p-0">
                        <input min="0" type="number" name="production_usage[]" class="form-control rounded-0" value="">
                    </td>
                    <td class="m-0 p-0">
                        <input min="0" type="number" name="production_export[]" class="form-control rounded-0" value="">
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
                    <tr>
                        <td class="m-0 p-0 w-50">
                            <select name="power_building_id[]" class="form-control rounded-0">
                                <?php foreach ($buildings as $building) : ?>
                                    <option <?php if ($power->buildings_id == $building->id) echo 'selected' ?>
                                            value="<?= $building->id ?>"><?= $building->name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="m-0 p-0 w-25">
                            <input min="0" type="number" name="power_amount[]" class="form-control rounded-0"
                                   value="<?= $power->building_ammount ?>">
                        </td>
                        <td class="m-0 p-0 w-25">
                            <input min="0" type="number" name="power_clock_speed[]" class="form-control rounded-0"
                                   value="<?= $power->clock_speed ?>">
                        </td>
                        <td class="w-25 m-0 p-0">
                            <input type="number" name="power_Consumption[]" class="form-control rounded-0" value="<?= $power->building_ammount * $power->power_used * ($power->clock_speed / 100) ?>">
                        </td>

                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td class="m-0 p-0 w-50">
                        <select name="power_building_id[]" class="form-control rounded-0 building-id"
                                onchange="addInputRow('building-id')">
                            <?php foreach ($buildings as $building) : ?>
                                <option value="<?= $building->id ?>"><?= $building->name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="m-0 p-0 w-25">
                        <input min="0" type="number" name="power_amount[]" class="form-control rounded-0">
                    </td>
                    <td class="m-0 p-0 w-25">
                        <input min="0" max="250" type="number" name="power_clock_speed[]" class="form-control rounded-0" value="100">
                    </td>
                    <td class="w-25 m-0 p-0">
                        <input type="number" name="power_Consumption[]" class="form-control rounded-0">
                    </td>
                </tr>
                <tr>
                    <td colspan="1" class="table-dark">
                        Total:
                    </td>
                    <td colspan="2"></td>
                    <td class="w-25 m-0 p-0">
                        <input type="number" name="total_consumption" class="form-control rounded-0" value="<?= $productLine->power_consumbtion ?>">
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



