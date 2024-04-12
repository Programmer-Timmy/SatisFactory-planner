<?php
$powerProductionBuildings = buildings::getPowerBuildings();
global $gameSave;
if ($_POST && isset($_POST['Biomass_Burner'])) {
    // Assuming you've included or defined the Database class somewhere
    $gameSaveId = $_GET['id'];

    $totalProduction = 0;
    foreach ($powerProductionBuildings as $building) {
        $totalProduction += $_POST[str_replace(' ', '_', $building->name)] * $building->power_generation;
    }

    GameSaves::updatePowerProduction($gameSaveId, $_POST['Biomass_Burner'], $_POST['Coal_Generator'], $_POST['Fuel_Generator'], $_POST['Nuclear_Power_Plant'], $totalProduction);

    echo "<script>location.href = 'game_save?id=$gameSaveId';</script>";
    exit();

}

?>

<div class="modal" id="updatePowerProduction" tabindex="-1" aria-labelledby="popupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="popupModalLabel">Add production line</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <?php if (!empty($powerProductionBuildings)) : ?>
                        <?php foreach ($powerProductionBuildings as $building) : ?>
                            <div class="form-group">
                                <label class="form-check-label" for="<?= $building->id ?>">
                                    <?= $building->name ?>
                                </label>
                                <input type="number" class="form-control" min="0" value="<?= $gameSave->{strtolower(str_replace(' ', '_', $building->name))} ?>" id="<?= str_replace(' ', '_', $building->name) ?>" name="<?= str_replace(' ', '_', $building->name) ?>">
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p>No buildings available</p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Add Production Line</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    document.getElementById('update_power_production').addEventListener('click', function () {
        const updatePowerProduction = new bootstrap.Modal(document.getElementById('updatePowerProduction'));
        updatePowerProduction.show();
    });
</script>