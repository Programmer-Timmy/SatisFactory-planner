<?php
$LIMIT = 20;
$searchYear = $_GET['year'] ?? date('Y');

$fourOFourLogs = ErrorHandeler::getAll404Logs($LIMIT);
$threeOFourLogs = ErrorHandeler::getAll403Logs($LIMIT);
$YearlyLogs = ErrorHandeler::getYearlyLogs($searchYear);

$availableYears = ErrorHandeler::getAvailableYears();
$availableIpAddresses = ErrorHandeler::getAvailableIpAddresses();
$topTenFourOFourLogs = ErrorHandeler::getTopTen404Logs();
$topTenThreeOFourLogs = ErrorHandeler::getTopTen403Logs();

$monthlySortedLogs = [];

// Sort logs by month and error type
foreach ($YearlyLogs[0] as $monthly404Log) {
    $month = date('F', strtotime($monthly404Log->error_timestamp));
    $monthlySortedLogs[$month][404][] = $monthly404Log;
}

foreach ($YearlyLogs[1] as $monthly403Log) {
    $month = date('F', strtotime($monthly403Log->error_timestamp));
    $monthlySortedLogs[$month][403][] = $monthly403Log;
}

// Convert timestamps to local time
foreach ($fourOFourLogs as $fourOFourLog) {
    $fourOFourLog->error_timestamp = GlobalUtility::dateTimeToLocal($fourOFourLog->error_timestamp);
}

foreach ($threeOFourLogs as $threeOFourLog) {
    $threeOFourLog->error_timestamp = GlobalUtility::dateTimeToLocal($threeOFourLog->error_timestamp);
}
?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<div class="px-md-4 px-2">
    <div class="row mb-4 align-items-center">
        <div class="col-lg-4"></div>
        <div class="col-lg-4">
            <h1 class="text-center">Logs Overview</h1>
        </div>
        <div class="col-lg-4 text-end">
            <a href="/admin" class="btn btn-primary">Return to admin page</a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-body h-100">
                    <div class="row align-items-center">
                        <div class="col-lg-4"></div>
                        <div class="col-lg-4">
                            <h3 class="card-title text-center">Monthly Logs</h3>
                        </div>
                        <div class="col-lg-4">
                            <div class="d-flex justify-content-end">
                                <select class="form-select w-auto" id="yearSelect"
                                        onchange="window.location.href = '/admin/errorLogs?year=' + this.value">
                                    <?php foreach ($availableYears as $year): ?>
                                        <option value="<?= $year->year ?>" <?= (isset($_GET['year']) && $_GET['year'] == $year->year) ? 'selected' : '' ?>><?= $year->year ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <?php if (empty($YearlyLogs)): ?>
                        <div class="alert alert-info text-center" role="alert">
                            No logs have been made this year.
                        </div>
                    <?php else: ?>
                        <div id="chart_div" style="width: 100%; height: 400px;"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-body h-100">
                    <div class="row align-items-center">
                        <div class="col-lg-4"></div>
                        <div class="col-lg-4">
                            <h3 class="card-title text-center">Yearly Logs</h3>
                        </div>
                        <div class="col-lg-4">
                            <div class="d-flex justify-content-end">
                                <select class="form-select w-auto" id="yearSelect"
                                        onchange="window.location.href = '/admin/errorLogs?year=' + this.value">
                                    <?php foreach ($availableYears as $year): ?>
                                        <option value="<?= $year->year ?>" <?= (isset($_GET['year']) && $_GET['year'] == $year->year) ? 'selected' : '' ?>><?= $year->year ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <?php if (empty($YearlyLogs)): ?>
                        <div class="alert alert-info text-center" role="alert">
                            No logs have been made this year.
                        </div>
                    <?php else: ?>
                        <div id="pie_chart_div" style="width: 100%; height: 400px;"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <h2 class="text-center mb-4">Top 10 Logs</h2>
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-body h-100">
                    <h3 class="card-title text-center">404 Logs</h3>
                    <?php if (empty($topTenFourOFourLogs)): ?>
                        <div class="alert alert-info text-center" role="alert">
                            No 404 logs have been made.
                        </div>
                    <?php else: ?>
                        <?= GlobalUtility::createTable($topTenFourOFourLogs, ['requested_url', 'count'], enableBool: false) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-body h-100">
                    <h3 class="card-title text-center">403 Logs</h3>
                    <?php if (empty($topTenThreeOFourLogs)): ?>
                        <div class="alert alert-info text-center" role="alert">
                            No 403 logs have been made.
                        </div>
                    <?php else: ?>
                        <?= GlobalUtility::createTable($topTenThreeOFourLogs, ['requested_url', 'count'], enableBool: false) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>


    <div class="row">
        <h2 class="text-center mb-4">Latest Logs</h2>
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-body h-100">
                    <div class="row align-items-center pb-2">
                        <div class="col-lg-4">
                        </div>
                        <div class="col-lg-4">
                            <h3 class="card-title text-center">404 Logs</h3>
                        </div>
                        <div class="col-lg-4">
                            <div class="d-flex justify-content-end">
                                <input type="search" class="form-control" id="four-o-four-search" placeholder="Search">
                            </div>
                        </div>
                    </div>
                    <?php if (empty($fourOFourLogs)): ?>
                        <div class="alert alert-info text-center" role="alert">
                            No 404 logs have been made.
                        </div>
                    <?php else: ?>
                        <?= GlobalUtility::createTable($fourOFourLogs, ['username', 'requested_url', 'ip_address', 'error_timestamp']) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-body h-100">
                    <div class="row align-items-center pb-2 ">
                        <div class="col-lg-4">
                        </div>
                        <div class="col-lg-4">
                            <h3 class="card-title text-center">403 Logs</h3>
                        </div>
                        <div class="col-lg-4">
                            <div class="d-flex justify-content-end">
<!--                                button to select filters-->
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                                    Filter
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php if (empty($threeOFourLogs)): ?>
                        <div class="alert alert-info text-center" role="alert">
                            No 403 logs have been made.
                        </div>
                    <?php else: ?>
                        <?= GlobalUtility::createTable($threeOFourLogs, ['username', 'requested_url', 'ip_address', 'error_timestamp']) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    google.charts.load('current', {'packages': ['corechart']});
    google.charts.setOnLoadCallback(drawCharts);

    function drawCharts() {
        drawBarChart();
        drawPieChart();
    }

    function drawBarChart() {
        const data = google.visualization.arrayToDataTable([
            ['Month', '403', '404'],
            <?php foreach ($monthlySortedLogs as $month => $logs): ?>
            ['<?= $month ?>', <?= isset($logs[403]) ? count($logs[403]) : 0 ?>, <?= isset($logs[404]) ? count($logs[404]) : 0 ?>],
            <?php endforeach; ?>
        ]);

        const options = {
            title: 'Monthly Logs',
            vAxis: {title: 'Logs'},
            hAxis: {title: 'Month'},
            isStacked: true,
            seriesType: 'bars'
        };

        const chart = new google.visualization.ComboChart(document.getElementById('chart_div'));
        chart.draw(data, options);
    }

    function drawPieChart() {
        const data = google.visualization.arrayToDataTable([
            ['Error Type', 'Count'],
            ['403 Errors', <?= count($YearlyLogs[1]) ?>],
            ['404 Errors', <?= count($YearlyLogs[0]) ?>]
        ]);

        const options = {
            title: 'Error Type Distribution'
        };

        const chart = new google.visualization.PieChart(document.getElementById('pie_chart_div'));
        chart.draw(data, options);
    }

    // Redraw charts on window resize for responsiveness
    window.addEventListener('resize', drawCharts);
</script>
