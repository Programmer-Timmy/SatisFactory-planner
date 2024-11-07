<?php
$LIMIT = 10;
$year = $_GET['year'] ?? date('Y');

$loginAttempts = AuthControler::getAllLoginAttempts(limit: $LIMIT);
$successfulAttempts = AuthControler::getSuccessfulLoginAttempts($year);
$failedAttempts = AuthControler::getFailedLoginAttempts($year);

$availableYears = Database::query("SELECT DISTINCT YEAR(login_timestamp) as year FROM login_attempts");

foreach ($loginAttempts as $loginAttempt) {
    $loginAttempt->login_timestamp = GlobalUtility::dateTimeToLocal($loginAttempt->login_timestamp);
}

$anvalibleIpAddresses = Database::query("SELECT DISTINCT ip_address FROM login_attempts");
$anvalibleUsernames = Database::query("SELECT DISTINCT username FROM users");

$yearSelect = '<select class="form-select w-auto mx-2" onchange="window.location.href = \'/admin/loginAttempts?year=\' + this.value">' . implode('', array_map(fn($availableYear) => '<option value="' . $availableYear->year . '" ' . ($availableYear->year == $year ? 'selected' : '') . '>' . $availableYear->year . '</option>', $availableYears)) . '</select>';
$ipSelect = '<select class="form-select w-auto mx-2" onchange="window.location.href = \'/admin/loginAttempts?ip=\' + this.value">' . implode('', array_map(fn($anvalibleIp) => '<option value="' . $anvalibleIp->ip_address . '">' . $anvalibleIp->ip_address . '</option>', $anvalibleIpAddresses)) . '</select>';
$usernameSelect = '<select class="form-select w-auto mx-2" onchange="window.location.href = \'/admin/loginAttempts?username=\' + this.value">' . implode('', array_map(fn($anvalibleUsername) => '<option value="' . $anvalibleUsername->username . '">' . $anvalibleUsername->username . '</option>', $anvalibleUsernames)) . '</select>';
$failedOrSuccessSelect = '<select class="form-select w-auto mx-2" onchange="window.location.href = \'/admin/loginAttempts?success=\' + this.value"><option value="1">Success</option><option value="0">Failed</option></select>';
?>


<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<div class="px-md-4 px-2">
    <div class="row align-items-center mb-4">
        <div class="col-md-4"></div>
        <div class="col-md-4">
            <h1 class="text-center">Login Attempts</h1>
        </div>
        <div class="col-md-4 text-center text-lg-end">
            <a href="/admin" class="btn btn-primary">Return to admin page</a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <?php GlobalUtility::renderCard(
                    'Monthly Login Attempts',
                    '<div id="monthlyLoginAttempts" style="width: 100%; height: 400px;"></div>',
                    [$yearSelect]
            ); ?>
        </div>
        <div class="col-lg-6 mb-4">
            <?php GlobalUtility::renderCard(
                    'Yearly Login Attempts',
                    '<div id="yearlyLoginAttempts" style="width: 100%; height: 400px;"></div>',
                    [$yearSelect]
            ); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?php
            $blockedAttempts = AuthControler::getBlockedLoginAttempts($year);
            $blockedString = '';
            if (empty($blockedAttempts)) {
                $blockedString = '<div class="alert alert-info text-center" role="alert">No blocked IPs found.</div>';
            } else {
                $blockedString = GlobalUtility::createTable($blockedAttempts, ['ip_address', 'login_timestamp'], enableBool: false);
            }

            GlobalUtility::renderCard(
                    'Blocked IPs history',
                    $blockedString,
                    [$yearSelect]
            ); ?>
        </div>
        <div class="col-md-6">
            <?php GlobalUtility::renderCard(
                    'Login Attempts',
                    GlobalUtility::createTable($loginAttempts, ['username', 'ip_address', 'success', 'login_timestamp'], enableBool: false),
                    [$yearSelect, $ipSelect, $usernameSelect, $failedOrSuccessSelect],
                    foreBottomControls: true
            //TODO: implement the search functionality
            ); ?>
        </div>
    </div>
</div>

<script type="application/javascript">
    google.charts.load('current', {'packages': ['corechart']});
    google.charts.setOnLoadCallback(drawCharts);

    function drawCharts() {
        drawMonthlyLoginAttempts();
        drawYearlyLoginAttempts();

    }

    function drawMonthlyLoginAttempts() {
        const monthlyLoginAttempts = google.visualization.arrayToDataTable([
            ['Month', 'Successful', 'Failed'],
            <?php
            $monthlyLoginAttempts = [];

            foreach ($successfulAttempts as $successfulAttempt) {
                $month = date('F', strtotime($successfulAttempt->login_timestamp));
                if (!isset($monthlyLoginAttempts[$month])) {
                    $monthlyLoginAttempts[$month] = [$month, 0, 0];
                }
                $monthlyLoginAttempts[$month][1]++;
            }

            foreach ($failedAttempts as $failedAttempt) {
                $month = date('F', strtotime($failedAttempt->login_timestamp));
                if (!isset($monthlyLoginAttempts[$month])) {
                    $monthlyLoginAttempts[$month] = [$month, 0, 0];
                }
                $monthlyLoginAttempts[$month][2]++;
            }

            foreach ($monthlyLoginAttempts as $monthlyLoginAttempt) {
                echo "['{$monthlyLoginAttempt[0]}', {$monthlyLoginAttempt[1]}, {$monthlyLoginAttempt[2]}],";
            }
            ?>
        ]);

        const options = {
            title: 'Monthly Login Attempts',
            vAxis: {title: 'Attempts'},
            hAxis: {title: 'Month'},
            isStacked: true,
            seriesType: 'bars'
        };

        const chart = new google.visualization.ComboChart(document.getElementById('monthlyLoginAttempts'));
        chart.draw(monthlyLoginAttempts, options);
    }


    function drawYearlyLoginAttempts() {
        const yearlyLoginAttempts = google.visualization.arrayToDataTable([
            ['Error Type', 'Count'],
            ['Successful', <?= count($successfulAttempts) ?>],
            ['Failed', <?= count($failedAttempts) ?>],
        ]);

        const options = {
            title: 'Yearly Login Attempts',
        };

        const chart = new google.visualization.PieChart(document.getElementById('yearlyLoginAttempts'));
        chart.draw(yearlyLoginAttempts, options);
    }

    window.addEventListener('resize', drawCharts);
</script>