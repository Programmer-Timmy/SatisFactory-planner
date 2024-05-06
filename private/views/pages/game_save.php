<?php

if (!isset($_GET['id'])) {
    header('Location: /');
    exit();
}

$gameSave = GameSaves::getSaveGameById($_GET['id']);
$outputs = Outputs::getAllOutputs();


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

$_SESSION['lastVisitedSaveGame'] = $_GET['id'];
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
    }

    function update_total_power_consumption() {
        let total_power_consumption = 0;
        document.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
            if (checkbox.checked) {
                const parentElement = checkbox.parentElement;

                const grandparentElement = parentElement.parentElement;

                const previousSibling = grandparentElement.previousElementSibling;

                const nextPreviousSibling = previousSibling.previousElementSibling;

                const textContent = nextPreviousSibling.innerText;

                total_power_consumption += parseInt(textContent);
            }
        });

        data.setValue(0, 1, total_power_consumption);
        chart.draw(data, options);

        var alertNode = $('#power-alert')[0];

        if (total_power_consumption > <?= $gameSave->total_power_production ?>) {
            alertNode.classList.remove('hidden');
            sleep(200).then(() => {
                alertNode.classList.add('show');
            });
        } else {
            alertNode.classList.remove('show');
            sleep(200).then(() => {
                alertNode.classList.add('hidden');
            });

        }
    }

    async function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

</script>
<div class="container">
    <h1 class="text-center pb-3">Game Save [<?= $gameSave->title ?>]</h1>
    <div class="row">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Production Lines</h2>
                <button id="add_product_line" class="btn btn-primary"><i class="fa-solid fa-plus"></i></button>
            </div>
            <?php if (empty($productionLines)) : ?>
                <h4 class="text-center mt-3">No Production Lines Found</h4>
            <?php else: ?>
            <div class="overflow-auto">
                <table class="table table-striped">
                    <thead>
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
                                <a href="production_line?id=<?= $productionLine->id ?>" class="btn btn-primary"><i
                                            class="fa-solid fa-gears"></i></a>
                            </td>
                            <td>
                                <a href="game_save?id=<?= $gameSave->id ?>&productDelete=<?= $productionLine->id ?>"
                                   onclick="return confirm('Are you sure you want to delete this production line?')"
                                   class="btn btn-danger">X</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Power Consumption</h2>
                <button id="update_power_production" class="btn btn-primary"><i class="fa-solid fa-bolt-lightning"></i>
                </button>
            </div>
            <div class="alert alert-danger fade show <?php if ($total_power_consumption <= $gameSave->total_power_production) echo 'hidden'; ?>"
                 id="power-alert" role="alert">
                <i class="fa-solid fa-triangle-exclamation"></i> Power Consumption is higher than available power
            </div>
            <div id="chart_div"></div>


            <h2>Outputs</h2>
            <table class="table table-striped">
                <thead>
                <tr>
                    <th scope="col">Item</th>
                    <th scope="col">Ammount</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($outputs as $output) : ?>
                    <tr>
                        <td><?= $output->item ?></td>
                        <td><?= $output->ammount ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
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
                active: active
            })
        }).catch(error => {
            console.error('Error:', error);
        });
        update_total_power_consumption();
    }
</script>

<?php require_once '../private/views/Popups/addProductionLine.php'; ?>
<?php require_once '../private/views/Popups/updatePowerProduction.php'; ?>
