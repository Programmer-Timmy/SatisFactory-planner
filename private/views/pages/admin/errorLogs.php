<?php
$LIMIT = 20;
$searchYear = $_GET['year'] ?? date('Y');
$fourOFourIpFilter = $_GET['404ip'] ?? null;
$fourOFourUrlFilter = $_GET['404url'] ?? null;
$threeOFourIpFilter = $_GET['403ip'] ?? null;
$threeOFourUrlFilter = $_GET['403url'] ?? null;

$fourOFourLogs = ErrorHandeler::getAll404Logs($LIMIT, $fourOFourIpFilter, $fourOFourUrlFilter);
$threeOFourLogs = ErrorHandeler::getAll403Logs($LIMIT, $threeOFourIpFilter, $threeOFourUrlFilter);
$YearlyLogs = ErrorHandeler::getYearlyLogs($searchYear);

$availableYears = ErrorHandeler::getAvailableYears();
$availableFourOThreePages = ErrorHandeler::getAvailable403Pages();
$availableFourOFourPages = ErrorHandeler::getAvailable404Pages();
$availableFourOThreeIpAddresses = ErrorHandeler::getAvailable403IpAddresses();
$availableFourOFourIpAddresses = ErrorHandeler::getAvailable404IpAddresses();

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
                        <div class="col-lg-4"></div>
                        <div class="d-flex justify-content-center col-12">
                            <select class="form-select w-auto mx-3" id="404ipFilter" onchange="applyFilters('404')">
                                <option value="">Filter by IP</option>
                                <?php foreach ($availableFourOFourIpAddresses as $ip): ?>
                                    <option value="<?= $ip->ip_address ?>" <?= $fourOFourIpFilter == $ip->ip_address ? 'selected' : '' ?>><?= $ip->ip_address ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select class="form-select w-auto mx-3" id="404urlFilter" onchange="applyFilters('404')">
                                <option value="">Filter by URL</option>
                                <?php foreach ($availableFourOFourPages as $page): ?>
                                    <option value="<?= $page->requested_url ?>" <?= $fourOFourUrlFilter == $page->requested_url ? 'selected' : '' ?>><?= $page->requested_url ?></option>
                                <?php endforeach; ?>
                            </select>
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
                        <div class="col-lg-4"></div>
                        <div class="d-flex justify-content-center col-12">
                            <select class="form-select w-auto mx-3" id="403ipFilter" onchange="applyFilters('403')">
                                <option value="">Filter by IP</option>
                                <?php foreach ($availableFourOThreeIpAddresses as $ip): ?>
                                    <option value="<?= $ip->ip_address ?>" <?= $threeOFourIpFilter == $ip->ip_address ? 'selected' : '' ?>><?= $ip->ip_address ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select class="form-select w-auto mx-3" id="403urlFilter" onchange="applyFilters('403')">
                                <option value="">Filter by URL</option>
                                <?php foreach ($availableFourOThreePages as $page): ?>
                                    <option value="<?= $page->requested_url ?>" <?= $threeOFourUrlFilter == $page->requested_url ? 'selected' : '' ?>><?= $page->requested_url ?></option>
                                <?php endforeach; ?>
                            </select>
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
    let existingFilters = new URLSearchParams(window.location.search);
    let ipFilter = existingFilters.get('ip');
    let urlFilter = existingFilters.get('url');
    let ipYear = existingFilters.get('year');

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

    function applyFilters(type) {
        const ip = document.getElementById(`${type}ipFilter`).value;
        const url = document.getElementById(`${type}urlFilter`).value;

        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set(`${type}ip`, ip);
        urlParams.set(`${type}url`, url);

        window.location.href = window.location.pathname + '?' + urlParams.toString();
    }


    // Redraw charts on window resize for responsiveness
    window.addEventListener('resize', drawCharts);
</script>

