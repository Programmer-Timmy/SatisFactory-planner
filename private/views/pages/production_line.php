<?php
$productLineId = $_GET['id'];
$productLine = ProductionLines::getProductionLineById($productLineId);

if (empty($productLine) || !ProductionLines::checkProductionLineVisability($productLine->game_saves_id, $_SESSION['userId'])) {
    header('Location: /');
    exit();
}

$imports = ProductionLines::getImportsByProductionLine($productLine->id);
$items = Items::getAllItems();

?>

<div class="mt-5 px-5">
    <h1 class="text-center pb-3"><?= $productLine->title ?></h1>
    <div class="row">
        <div class="col-md-2">
            <h2>Imports</h2>
            <table class="table table-striped" id="imports">
                <thead class="table-dark">
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Quantity Per/min</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($imports)) : ?>

                <?php endif; ?>
                <?php foreach ($imports as $import) : ?>
                    <tr>
                       <td class="m-0 p-0  w-75">
                           <select name="item_id" class="form-control rounded-0">
                               <?php foreach ($items as $item) : ?>
                                   <option <?php if($import->items_id == $item->id) echo 'selected' ?> value="<?= $item->id ?>"><?= $item->name ?></option>
                               <?php endforeach; ?>
                            </select>
                       </td>
                        <td class="m-0 p-0 w-25">
                            <input min="0" type="number" name="ammount" class="form-control rounded-0" value="<?= $import->ammount ?>">
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td class="m-0 p-0 w-75">
                        <select name="item_id" class="form-control rounded-0 input-item-id" onchange="addRow()">
                            <?php foreach ($items as $item) : ?>
                                <option value="<?= $item->id ?>"><?= $item->name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="m-0 p-0 w-25">
                        <input min="0" type="number" name="ammount" class="form-control rounded-0">
                    </td>
                </tr>

                </tbody>

            </table>
        </div>
        <div class="col-md-6">
            <h2>Recipes</h2>
        </div>
        <div class="col-md-4">
            <h2>Power</h2>
    </div>
</div>
<script src="js/tableFunctions.js"></script>



