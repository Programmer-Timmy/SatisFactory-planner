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
$anvalibleUsernames = Database::query("SELECT DISTINCT username, id FROM users");

// Year Select
$yearSelect = '
    <select class="form-select w-auto mx-2 mb-2" name="year">
        ' . implode('', array_map(function ($availableYear) use ($year) {
        $isSelected = $availableYear->year == $year ? 'selected' : '';
        return '<option value="' . $availableYear->year . '" ' . $isSelected . '>' . $availableYear->year . '</option>';
    }, $availableYears)) . '
    </select>';

// IP Address Select
$ipSelect = '
    <select class="form-select w-auto mx-2 mb-2" name="ip">
        <option value="">All IPs</option>
        ' . implode('', array_map(function ($anvalibleIp) {
        return '<option value="' . $anvalibleIp->ip_address . '">' . $anvalibleIp->ip_address . '</option>';
    }, $anvalibleIpAddresses)) . '
    </select>';

// Username Select
$usernameSelect = '
    <select class="form-select w-auto mx-2 mb-2" name="userName">
        <option value="">All users</option>
        ' . implode('', array_map(function ($anvalibleUsername) {
        return '<option value="' . $anvalibleUsername->id . '">' . $anvalibleUsername->username . '</option>';
    }, $anvalibleUsernames)) . '
    </select>';

// Failed or Success Select
$failedOrSuccessSelect = '
    <select class="form-select w-auto mx-2 mb-2" name="type">
        <option value="">All types</option>
        <option value="1">Success</option>
        <option value="0">Failed</option>
    </select>';

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
                    GlobalUtility::createTable($loginAttempts, ['username', 'ip_address', 'success', 'login_timestamp'], enableBool: false, customId: 'loginAttemptsTable'),
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
<script>
    // ajax search
    console.log('search');
    const ip = document.querySelector('select[name="ip"]');
    const year = document.querySelector('select[name="year"]');
    const userName = document.querySelector('select[name="userName"]');
    const type = document.querySelector('select[name="type"]');
    const loginAttemptsTable = document.getElementById('loginAttemptsTable');

    ip.addEventListener('change', search);
    year.addEventListener('change', search);
    userName.addEventListener('change', search);
    type.addEventListener('change', search);

    function search() {
        const formData = new FormData();
        formData.append('ip', ip.value);
        formData.append('year', year.value);
        formData.append('user', userName.value);
        formData.append('type', type.value);

        $.ajax({
            url: '/admin/loginAttempts/search',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': '<?= $_SESSION['csrf_token'] ?>'
            },
            success: function (data) {
                loginAttemptsTable.innerHTML = data;
            },
            error: function (data) {
                loginAttemptsTable.innerHTML = data.responseJSON.error;
            }
        });
    }
</script>