<?php
global $gameSave;
global $changelog;

$powerProductionBuildings = buildings::getPowerBuildings();
$powerProduction = PowerProduction::getPowerProduction($gameSave->id);
?>

<div class="modal fade modal-lg" id="updatePowerProduction" tabindex="-1" aria-labelledby="popupModalLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="popupModalLabel">Power Production</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="row px-2 pb-1">
                        <div class="col-5 col-lg-6 ps-3 pe-1">
                            <label for="building">Building</label>
                        </div>
                        <div class="col-2 px-1">
                            <label for="amount">Amount</label>
                        </div>
                        <div class="col-3 col-lg-2 px-1">
                            <label for="clock_speed">Clock Speed</label>
                        </div>
                        <div class="col-2 ps-1 pe-3">
                        </div>
                    </div>
                    <div id="powerProduction">
                        <?php if (!empty($powerProduction)) : ?>
                            <?php foreach ($powerProduction as $power) : ?>
                                <div class="card mb-2" id="powerProductionCard<?= $power->id ?>">
                                    <div class="card-body p-2 row d-flex justify-content-between align-items-center">
                                        <div class="col-5 ps-3 pe-1 col-lg-6">
                                            <h6 class="m-0"><?= $power->building_name ?></h6>
                                        </div>
                                        <div class="col-2 px-1">
                                            <input type="number" class="form-control" id="amount" name="amount"
                                                   min="1" max="1000" value="<?= $power->amount ?>">
                                        </div>
                                        <div class="col-2 px-1">
                                            <input type="number" class="form-control" id="clock_speed"
                                                   name="clock_speed" min="1" max="250" step="any"
                                                   value="<?= $power->clock_speed ?>">
                                        </div>
                                        <div class="col-3 col-lg-2 text-end ps-1 pe-3">
                                            <button type="button" class="btn btn-danger deletePowerProduction"
                                                    data-id="<?= $power->building_id ?>">Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <h5 class="text-center mt-2">No power production buildings added yet</h5>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($powerProductionBuildings)) : ?>
                        <devider class="dropdown-divider"></devider>
                        <hr>
                        <div class="card mb-2" id="powerProductionCardNew">
                            <div class="card-body p-2 row ">
                                <div class="col-5 col-lg-6 ps-3 pe-1">
                                    <select class="form-select" id="building" name="building">
                                        <option value="" disabled selected>Select a building</option>
                                        <?php foreach ($powerProductionBuildings as $building) : ?>
                                            <option value="<?= $building->id ?>"><?= $building->name ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Please select a building</div>
                                </div>
                                <div class="col-2  px-1">
                                    <input type="number" class="form-control" id="amount" name="amount" min="1"
                                           max="1000"
                                           value="1">
                                    <div class="invalid-feedback">Amount must be 1-1000</div>
                                </div>
                                <div class="col-2 px-1">
                                    <input type="number" class="form-control" id="clock_speed" name="clock_speed"
                                           min="1"
                                           max="250" step="any" value="100">
                                    <div class="invalid-feedback">Clock speed must be 1-250</div>
                                </div>
                                <div class="col-3 col-lg-2 text-end px-1 pe-3">
                                    <button type="button" class="btn btn-primary" id="addPowerProduction">Add</button>
                                </div>
                            </div>

                        </div>
                    <?php endif; ?>
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

<script src="js/powerProduction.js?v=<?=$changelog['version']?>"></script>