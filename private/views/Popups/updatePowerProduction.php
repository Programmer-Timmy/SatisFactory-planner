<?php
$powerProductionBuildings = buildings::getPowerBuildings();
global $gameSave;
if ($_POST && isset($_POST['Biomass_Burner'])) {
    // Assuming you've included or defined the Database class somewhere
    $gameSaveId = $_GET['id'];

    $totalProduction = 0;
    foreach ($powerProductionBuildings as $building) {
        if ($building->name == 'Alien Power Augmenter') {
            $totalProduction += $_POST[str_replace(' ', '_', $building->name)] * $building->power_generation;
            $totalProduction += $_POST['Boosted_' . str_replace(' ', '_', $building->name)] * $building->power_generation;
        } else {
            $totalProduction += $_POST[str_replace(' ', '_', $building->name)] * $building->power_generation;
        }
    }
    $bonus_percentage = 1;
    $bonus_percentage += 0.1 * $_POST['Alien_Power_Augmenter'];
    $bonus_percentage += 0.3 * $_POST['Boosted_Alien_Power_Augmenter'];
    $totalProduction *= $bonus_percentage;

    GameSaves::updatePowerProduction($gameSaveId, $_POST['Biomass_Burner'], $_POST['Coal-Powered_Generator'], $_POST['Fuel-Powered_Generator'], $_POST['Nuclear_Power_Plant'], $_POST['Alien_Power_Augmenter'], $_POST['Boosted_Alien_Power_Augmenter'], $totalProduction);

    echo "<script>location.href = 'game_save?id=$gameSaveId';</script>";
    exit();

}

?>

<div class="modal fade" id="updatePowerProduction" tabindex="-1" aria-labelledby="popupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="popupModalLabel">Power Production</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <?php if (!empty($powerProductionBuildings)) : ?>
                        <?php foreach ($powerProductionBuildings as $building) : ?>
                            <?php if ($building->name == 'Alien Power Augmenter') : ?>
                                <div class="row">
                                    <div class="form-group col-6 pe-1">
                                        <label class="form-check-label" for="<?= $building->id ?>">
                                            <?= $building->name ?>
                                        </label>
                                        <input type="number" class="form-control" min="0" required
                                               value="<?= $gameSave->{strtolower(str_replace(' ', '_', $building->name))} ?>"
                                               id="<?= str_replace(' ', '_', $building->name) ?>"
                                               name="<?= str_replace(' ', '_', $building->name) ?>">
                                    </div>
                                    <div class="form-group col-6 ps-1">
                                        <label class="form-check-label" for="<?= $building->id ?>">
                                            Boosted Alien Augmenter
                                        </label>
                                        <input type="number" class="form-control" min="0" required
                                               value="<?= $gameSave->{strtolower('Boosted_' . str_replace(' ', '_', $building->name))} ?>"
                                               id=Boosted_"<?= str_replace(' ', '_', $building->name) ?>"
                                               name="Boosted_<?= str_replace(' ', '_', $building->name) ?>">
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="form-group">
                                    <label class="form-check-label" for="<?= $building->id ?>">
                                        <?= $building->name ?>
                                    </label>
                                    <input type="number" class="form-control" min="0" required
                                           value="<?= $gameSave->{strtolower(str_replace(' ', '_', $building->name))} ?>"
                                           id="<?= str_replace(' ', '_', $building->name) ?>"
                                           name="<?= str_replace(' ', '_', $building->name) ?>">
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p>No buildings available</p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update Power Production</button>
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