<?php
global $productLine, $buildings, $powers;
?>
<div class="modal fade" id="showPowerModal" tabindex="-1" aria-labelledby="popupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="popupModalLabel">Power</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
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
                    <?php foreach ($powers as $power) : ?>
                        <tr <?= $power->user ? 'class="user"' : '' ?>>
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
                                <input min="0" type="number" name="power_clock_speed[]" step="any"
                                       class="form-control rounded-0 clock-speed"
                                       onchange="calculateConsumption(this)"
                                       value="<?= $power->clock_speed ?>">
                            </td>
                            <td class="w-25 m-0 p-0">
                                <input type="number" name="power_Consumption[]"
                                       class="form-control rounded-0 consumption" disabled
                                       onchange="calculateTotalConsumption()"
                                       value="<?= $power->building_ammount * $power->power_used * ($power->clock_speed / 100) ?>">
                                <input type="hidden" class="user" name="user[]" value="<?= $power->user ?>">
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
                            <input type="number" name="power_Consumption[]" disabled
                                   class="form-control rounded-0 consumption"
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
        </div>
    </div>
</div>

<script>
    document.getElementById('showPower').addEventListener('click', function () {
        const addProductionLine = new bootstrap.Modal(document.getElementById('showPowerModal'));
        addProductionLine.show();
    });
</script>
