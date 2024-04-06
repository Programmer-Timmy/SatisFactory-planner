<?php

if (!isset($_GET['id'])) {
    header('Location: /');
    exit();
}

$gameSave = GameSaves::getSaveGameById($_GET['id']);

if (empty($gameSave)) {
    header('Location: /');
    exit();
}

$productionLines = ProductionLines::getProductionLinesByGameSave($gameSave->id);
$total_power_consumption = 0;
foreach ($productionLines as $productionLine) {
    $total_power_consumption += $productionLine->power_consumbtion;
}

$_SESSION['lastVisitedSaveGame'] = $_GET['id'];
?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
    google.charts.load('current', {'packages':['gauge']});
    google.charts.setOnLoadCallback(drawChart);

    function drawChart() {

        var available_power  = 1000;

        var data = google.visualization.arrayToDataTable([
            ['Label', 'Value'],
            ['Power', <?= $total_power_consumption ?>]
        ]);

        var options = {
            redFrom: available_power * 0.9, redTo: available_power,
            yellowFrom:available_power * 0.75, yellowTo: available_power * 0.9,
            minorTicks: 5,
            max: available_power
        };

        var chart = new google.visualization.Gauge(document.getElementById('chart_div'));

        chart.draw(data, options);
    }
</script>
<div class="container mt-5">
    <h1 class="text-center pb-3">Game Save [<?= $gameSave->title ?>]</h1>
    <div class="row">
        <div class="col-md-8">
            <h2>Production Lines</h2>
            <?php if (empty($productionLines)) : ?>
                <h4 class="text-center mt-3">No Production Lines Found</h4>
            <?php else: ?>
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Power Consumption</th>
                        <th scope="col">Updated At</th>
                        <th scope="col"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($productionLines as $productionLine) : ?>
                        <tr>
                            <td><?= $productionLine->name ?></td>
                            <td><?= $productionLine->power_consumbtion ?></td>
                            <td><?= $productionLine->updated_at ?></td>
                            <td><a href="production_line?id=<?= $productionLine->id ?>" class="btn btn-primary">View Production Line</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        </div>
        <div class="col-md-4">
            <h2>Power</h2>
            <div id="chart_div" ></div>

            <h2>Outputs</h2>

        </div>
    </div>
