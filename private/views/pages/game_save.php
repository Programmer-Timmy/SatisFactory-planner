<?php

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /');
    exit();
}

$gameSave = GameSaves::getSaveGameById($_GET['id']);
$outputs = Outputs::getAllOutputs($_GET['id']);

if (empty($gameSave)) {
    header('Location: /');
    exit();
}

$productionLines = ProductionLines::getProductionLinesByGameSave($gameSave->id);
$total_power_consumption = 0;
foreach ($productionLines as $productionLine) {
    if ($productionLine->active) {
        $total_power_consumption += $productionLine->power_consumbtion;
    }
}

if (isset($_GET['productDelete'])) {
    ProductionLines::deleteProductionLine($_GET['productDelete']);
    header('Location: game_save?id=' . $_GET['id']);
    exit();
}

GameSaves::setLastVisitedSaveGame($gameSave->id);

if (isset($_GET['layoutType'])) {
    GameSaves::changeCardView($_GET['id'], $_GET['layoutType']);
    header('Location: game_save?id=' . $_GET['id']);
    exit();
}
?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
    google.charts.load('current', {'packages': ['gauge']});
    google.charts.setOnLoadCallback(drawChart);

    let data;
    let chart;
    let options;

    function drawChart() {

        var available_power = <?= $gameSave->total_power_production ?>;

        data = google.visualization.arrayToDataTable([
            ['Label', 'Value'],
            ['Power', <?= $total_power_consumption ?>]
        ]);

        options = {
            redFrom: available_power * 0.9, redTo: available_power,
            yellowFrom: available_power * 0.75, yellowTo: available_power * 0.9,
            minorTicks: 5,
            max: available_power
        };

        chart = new google.visualization.Gauge(document.getElementById('chart_div'));

        chart.draw(data, options);

        if (available_power === 0) {
            $('#popover-power').popover('show');
        }
    }

    async function getPowerProduction() {
        return new Promise(function (resolve, reject) {
            $.ajax({
                type: 'POST',
                url: 'powerProduction/get',
                dataType: 'json',
                data: {
                    gameSaveId: <?= $gameSave->id ?>
                },
                success: function (data) {
                    if (data.success) {
                        resolve(data.powerProduction);
                    } else {
                        console.error(data.error);
                        reject(data.error);
                    }
                },
                error: function (error) {
                    console.error(error);
                }
            });
        });
    }

    async function update_total_power_consumption() {
        const total_power_consumption = getPowerConsumption();
        const total_power_production = await getPowerProduction();

        data.setValue(0, 1, total_power_consumption);
        chart.draw(data, options);

        checkIfPowerProductionIsHigherThanAvailablePower(total_power_consumption, total_power_production);
    }

    function getPowerConsumption() {
        let total_power_consumption = 0;
        document.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
            if (checkbox.checked) {
                let textContent;
                if (<?= $gameSave->card_view ?>) {
                    const parentElement = $(checkbox).closest('.card-body');

                    textContent = parentElement.find('.card-text')[0].innerText

                    textContent = textContent.replace("Power Consumption: ", '');

                } else {
                    const parentElement = checkbox.parentElement;

                    const grandparentElement = parentElement.parentElement;

                    const previousSibling = grandparentElement.previousElementSibling;

                    const nextPreviousSibling = previousSibling.previousElementSibling;

                    textContent = nextPreviousSibling.innerText;
                }

                total_power_consumption += parseInt(textContent);

            }
        });
        return total_power_consumption;
    }

    function checkIfPowerProductionIsHigherThanAvailablePower(total_power_consumption, gameSaveTotalPowerProduction) {
        if (total_power_consumption > gameSaveTotalPowerProduction) {
            $('#power-alert').removeClass('hidden');
            sleep(200).then(() => {
                $('#power-alert').addClass('show');
            });
        } else {
            $('#power-alert').removeClass('show');
            sleep(200).then(() => {
                $('#power-alert').addClass('hidden');
            });
        }
    }

    function updatePowerProduction(power) {
        options.max = power;
        options.redFrom = power * 0.9;
        options.redTo = power;
        options.yellowFrom = power * 0.75;
        options.yellowTo = power * 0.9;

        chart.draw(data, options);

        const total_power_consumption = getPowerConsumption();

        checkIfPowerProductionIsHigherThanAvailablePower(total_power_consumption, power);
    }

    async function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

</script>
<div class="container">
    <h1 class="text-center pb-3">Game Save - <?= $gameSave->title ?></h1>
    <div class="row">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Production Lines</h2>
                <div class="d-flex flex-nowrap">
                    <button class="btn btn-secondary me-2"
                            onclick="location.href = 'game_save?id=<?= $gameSave->id ?>&layoutType=<?= $gameSave->card_view === 1 ? 0 : 1 ?>'"
                            data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-title="<?= $gameSave->card_view === 1 ? 'Table View' : 'Card View' ?>">
                        <i class=" <?= $gameSave->card_view === 1 ? 'fa-solid fa-table' : 'fa-regular fa-square' ?>"></i>
                    </button>
                    <span id="popover-production" data-bs-toggle="popover" data-bs-placement="bottom"
                          opened="<?= empty($productionLines) ? 'false' : 'true' ?>"
                          data-bs-trigger="manual"
                          title="No Production Lines Added"
                          data-bs-content="Add an production line to start calculating and planning your production">
                        <div style="width: 40px; height: 38px;">
                           <button id="add_product_line" class="btn btn-primary"
                                   data-bs-title="Add Production Line"><i class="fa-solid fa-plus"></i></button>
                        </div>
                    </span>
                </div>
            </div>
            <?php if (empty($productionLines)) : ?>
                <h4 class="text-center mt-3">No Production Lines Found</h4>
            <?php elseif ($gameSave->card_view) : ?>
                <div class="row">
                    <?php foreach ($productionLines as $productionLine) : ?>
                        <div class="col-md-6 col-xl-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-dark text-white">
                                    <h5 class="card-title mb-0"><?= $productionLine->name ?></h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><strong>Power
                                            Consumption:</strong> <?= $productionLine->power_consumbtion ?></p>
                                    <p class="card-text"><strong>Updated
                                            At:</strong> <?= GlobalUtility::formatUpdatedTime($productionLine->updated_at) ?>
                                    </p>
                                    <div class="form-group">
                                        <label><strong>Active:</strong></label>
                                        <input type="checkbox" data-toggle="toggle" data-onstyle="success"
                                               onchange="changeActiveStats(<?= $productionLine->id ?>, this)"
                                               data-offstyle="danger" data-size="sm" data-onlabel="Yes"
                                               data-offlabel="No" <?= $productionLine->active ? 'checked' : '' ?>>
                                    </div>
                                </div>
                                <div class="card-footer d-flex justify-content-between">
                                    <a href="production_line?id=<?= $productionLine->id ?>" class="btn btn-primary"
                                       data-bs-toggle="tooltip" data-bs-placement="top"
                                       data-bs-title="Open Production Line">
                                        <i class="fa-solid fa-gears"></i> Open
                                    </a>
                                    <a href="game_save?id=<?= $gameSave->id ?>&productDelete=<?= $productionLine->id ?>"
                                       data-bs-toggle="tooltip" data-bs-placement="top"
                                       data-bs-title="Delete Production Line"
                                       onclick="return confirm('Are you sure you want to delete this production line?')"
                                       class="btn btn-danger">
                                        <i class="fa-solid fa-x"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="overflow-auto">
                    <table class="table table-striped">
                        <thead class="table-dark">
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Power Consumption</th>
                            <th scope="col">Updated At</th>
                            <th scope="col">Active</th>
                            <th scope="col"></th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($productionLines as $productionLine) : ?>
                            <tr>
                                <td><?= $productionLine->name ?></td>
                                <td><?= $productionLine->power_consumbtion ?></td>
                                <td>
                                    <?= GlobalUtility::formatUpdatedTime($productionLine->updated_at) ?>
                                </td>
                                <td>
                                    <input type="checkbox" data-toggle="toggle" data-onstyle="success"
                                           onchange="changeActiveStats(<?= $productionLine->id ?>, this)"
                                           data-offstyle="danger" data-size="sm" data-onlabel="Yes"
                                           data-offlabel="No" <?= $productionLine->active ? 'checked' : '' ?>>
                                </td>
                                <td>
                                    <div>
                                        <a href="production_line?id=<?= $productionLine->id ?>" class="btn btn-primary"
                                           data-bs-toggle="tooltip" data-bs-placement="top"
                                           data-bs-title="Open Production Line"><i
                                                    class="fa-solid fa-gears"></i></a>
                                </td>
                                <td>
                                    <a href="game_save?id=<?= $gameSave->id ?>&productDelete=<?= $productionLine->id ?>"
                                       data-bs-toggle="tooltip" data-bs-placement="top"
                                       data-bs-title="Delete Production Line"
                                       onclick="return confirm('Are you sure you want to delete this production line?')"
                                       class="btn btn-danger"><i class="fa-solid fa-x"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-lg-4">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Power Consumption</h2>
                <!--                popover when no power production is added-->
                <span id="popover-power" data-bs-toggle="popover" data-bs-placement="top"
                      title="No Power Production Added"
                      data-bs-trigger="manual"
                      data-bs-content="Add Power Production to have an prediction over your power capacity">
                    <div style="width: 40px; height: 38px;">
                        <button id="update_power_production" class="btn btn-primary" data-bs-toggle="tooltip"
                                data-bs-placement="top" data-bs-title="Update Power Production"><i
                                    class="fa-solid fa-bolt-lightning"></i>
                        </button>
                    </div>
                </span>
            </div>
            <div class="alert alert-danger fade show <?php if ($total_power_consumption <= $gameSave->total_power_production) echo 'hidden'; ?>"
                 id="power-alert" role="alert">
                <i class="fa-solid fa-triangle-exclamation"></i> Power Consumption is higher than available power
            </div>
            <div id="chart_div" class="d-flex justify-content-center"></div>
            <h2>Outputs</h2>
            <div id="output_table">
                <?php if (empty($outputs)) : ?>
                    <h4 class="text-center mt-3">No Outputs Found</h4>
                <?php else: ?>
                    <div class="accordion" id="productionLinesAccordion">
                        <?php foreach ($outputs as $lineTitle => $lineOutputs) :
                            $lineTitle = htmlspecialchars($lineTitle);
                            $lineId = preg_replace('/\s+/', '_', $lineTitle);
                            ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse-<?= $lineId ?>"
                                            aria-expanded="false"
                                            aria-controls="collapse-<?= $lineId ?>">
                                        <?= htmlspecialchars($lineTitle) ?>
                                    </button>
                                </h2>
                                <div id="collapse-<?= $lineId ?>"
                                     class="accordion-collapse collapse"
                                     data-bs-parent="#productionLinesAccordion">
                                    <div class="accordion-body p-0">
                                        <?php if (empty($lineOutputs)) : ?>
                                            <p>No Outputs for this line.</p>
                                        <?php else: ?>
                                            <table class="table table-striped m-0">
                                                <thead class="table-dark">
                                                <tr>
                                                    <th scope="col">Item</th>
                                                    <th scope="col">Amount</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php foreach ($lineOutputs as $output) : ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($output->item) ?></td>
                                                        <td><?= htmlspecialchars($output->ammount) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function changeActiveStats(productLineId, object) {
        let active = object.checked ? 1 : 0;
        //     use api give id and active
        fetch('/api/changeActiveStats', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: productLineId,
                active: active,
                gameSaveId: <?= $gameSave->id ?>
            })
        }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    let htmlString = data.html;
                    $('#output_table').html(htmlString);
                    update_total_power_consumption();
                } else {
                    console.error(data.error);
                }
            })
            .catch((error) => {
                console.error('Error:', error);
            });
    }
</script>

<?php
if (empty($productionLines)) {
    echo '<script>$(document).ready(function(){$(\'#popover-production\').popover(\'show\');});</script>';
}
?>
<?php
global $changelog;
if (DedicatedServer::getBySaveGameId($gameSave->id)) : ?>
    <script src="js/dedicatedServer.js?v=<?= $changelog['version'] ?>"></script>
    <script>
        new DedicatedServer(<?= $gameSave->id ?>);
    </script>

    <script>
        document.querySelectorAll('.accordion-button').forEach(button => {
            button.addEventListener('click', function () {
                console.log(this.getAttribute('aria-expanded'));
            });
        });

    </script>
<?php endif; ?>
<?php require_once '../private/views/Popups/addProductionLine.php'; ?>
<?php require_once '../private/views/Popups/updatePowerProduction.php'; ?>
