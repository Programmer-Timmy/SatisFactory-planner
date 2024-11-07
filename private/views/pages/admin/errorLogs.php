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

$yearSelect = '<select class="form-select w-auto" id="yearSelect" onchange="window.location.href = \'/admin/errorLogs?year=\' + this.value">';
foreach ($availableYears as $availableYear) {
    $yearSelect .= '<option value="' . $availableYear->year . '" ' . ($availableYear->year == $searchYear ? 'selected' : '') . '>' . $availableYear->year . '</option>';
}
$yearSelect .= '</select>';

?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<div class="px-md-4 px-2">
    <div class="row mb-4 align-items-center">
        <div class="col-lg-3"></div>
        <div class="col-lg-6">
            <h1 class="text-center">Logs Overview</h1>
        </div>
        <div class="col-lg-3 text-center text-lg-end">
            <a href="/admin" class="btn btn-primary">Return to admin page</a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <?php GlobalUtility::renderCard(
                'Monthly Logs',
                '<div id="chart_div" style="width: 100%; height: 400px;"></div>',
                [$yearSelect]
            ); ?>
        </div>

        <div class="col-lg-6 mb-4">
            <?php GlobalUtility::renderCard(
                'Yearly Logs',
                '<div id="pie_chart_div" style="width: 100%; height: 400px;"></div>',
                [$yearSelect]
            ); ?>
        </div>
    </div>

    <div class="row">
        <h2 class="text-center mb-4">Top 10 Logs</h2>
        <div class="col-lg-6 mb-4">
            <?php GlobalUtility::renderCard(
                '404 Logs',
                empty($topTenFourOFourLogs) ? '<div class="alert alert-info text-center" role="alert">No 404 logs have been made.</div>' : GlobalUtility::createTable($topTenFourOFourLogs, ['requested_url', 'count'], enableBool: false)
            ); ?>
        </div>

        <div class="col-lg-6 mb-4">
            <?php GlobalUtility::renderCard(
                '403 Logs',
                empty($topTenThreeOFourLogs) ? '<div class="alert alert-info text-center" role="alert">No 403 logs have been made.</div>' : GlobalUtility::createTable($topTenThreeOFourLogs, ['requested_url', 'count'], enableBool: false)
            ); ?>
        </div>
    </div>


    <div class="row">
        <h2 class="text-center mb-4">Latest Logs</h2>
        <div class="col-lg-6 mb-4">
            <?php GlobalUtility::renderCard(
                '404 Logs',
                empty($fourOFourLogs) ? '<div class="alert alert-info text-center" role="alert">No 404 logs have been made.</div>' : GlobalUtility::createTable($fourOFourLogs, ['username', 'requested_url', 'ip_address', 'error_timestamp']),
                [
                    generateIpFilterDropdown($availableFourOFourIpAddresses, $fourOFourIpFilter),
                    generateUrlFilterDropdown($availableFourOFourPages, $fourOFourUrlFilter)
                ],
                null,
                true
            );
            ?>
        </div>

        <div class="col-lg-6 mb-4">
            <?php GlobalUtility::renderCard(
                '403 Logs',
                empty($threeOFourLogs) ? '<div class="alert alert-info text-center" role="alert">No 403 logs have been made.</div>' : GlobalUtility::createTable($threeOFourLogs, ['username', 'requested_url', 'ip_address', 'error_timestamp']),
                [
                    generateIpFilterDropdown($availableFourOThreeIpAddresses, $threeOFourIpFilter, '403'),
                    generateUrlFilterDropdown($availableFourOThreePages, $threeOFourUrlFilter, '403')
                ],
                null,
                true
            );
            ?>
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


<?php


/**
 * Generates an HTML select dropdown for filtering by IP address.
 *
 * @param array $ipAddresses Array of objects containing `ip_address` and `count` properties.
 * @param string|null $selectedIp Currently selected IP for filtering.
 * @return string HTML for the IP filter dropdown.
 */
function generateIpFilterDropdown(array $ipAddresses, ?string $selectedIp = null, string $type = '404'): string {
    $html = '<select class="form-select w-auto mx-3" id="' . $type . 'ipFilter" onchange="applyFilters(\'' . $type . '\')">';
    $html .= '<option value="">Filter by IP</option>';

    foreach ($ipAddresses as $ip) {
        $isSelected = ($selectedIp === $ip->ip_address) ? 'selected' : '';
        $html .= sprintf(
            '<option value="%s" %s>%s (%d)</option>',
            htmlspecialchars($ip->ip_address),
            $isSelected,
            htmlspecialchars($ip->ip_address),
            $ip->count
        );
    }

    $html .= '</select>';
    return $html;
}

/**
 * Generates an HTML select dropdown for filtering by URL.
 *
 * @param array $urls Array of objects containing `requested_url` and `count` properties.
 * @param string|null $selectedUrl Currently selected URL for filtering.
 * @return string HTML for the URL filter dropdown.
 */
function generateUrlFilterDropdown(array $urls, ?string $selectedUrl = null, string $type = '404'): string {
    $html = '<select class="form-select w-auto mx-3" id="' . $type . 'urlFilter" onchange="applyFilters(\'' . $type . '\')">';
    $html .= '<option value="">Filter by URL</option>';

    foreach ($urls as $page) {
        $isSelected = ($selectedUrl === $page->requested_url) ? 'selected' : '';
        $html .= sprintf(
            '<option value="%s" %s>%s (%d)</option>',
            htmlspecialchars($page->requested_url),
            $isSelected,
            htmlspecialchars($page->requested_url),
            $page->count
        );
    }

    $html .= '</select>';
    return $html;
}

