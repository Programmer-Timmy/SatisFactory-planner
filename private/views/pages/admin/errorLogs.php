<?php

$fourOFourLogs = ErrorHandeler::getAll404Logs();
$threeOFourLogs = ErrorHandeler::getAll403Logs();

foreach ($fourOFourLogs as $fourOFourLog) {
    $fourOFourLog->error_timestamp = GlobalUtility::dateTimeToLocal($fourOFourLog->error_timestamp);
}
?>

<div class="container">
    <h1 class="text-center mb-4">Logs</h1>
    <a href="/admin" class="btn btn-primary w-100 mb-3">Return to admin page</a>
    <div class="card">
        <div class="card-body">
            <h3 class="card-title text-center">404 Logs</h3>
            <?php if (empty($fourOFourLogs)): ?>
                <div class="alert alert-info text-center" role="alert">
                    No 404 logs have been made.
                </div>
            <?php else: ?>
                <?= GlobalUtility::createTable($fourOFourLogs, ['username', 'requested_url', 'ip_address', 'referrer_url', 'error_timestamp']) ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="card mt-3">
        <div class="card-body">
            <h3 class="card-title">403 Logs</h3>
            <?php if (empty($threeOFourLogs)): ?>
                <div class="alert alert-info text-center" role="alert">
                    No 403 logs have been made.
                </div>
            <?php else: ?>
                <?= GlobalUtility::createTable($threeOFourLogs, ['username', 'requested_url', 'ip_address', 'referrer_url', 'error_timestamp']) ?>
            <?php endif; ?>
        </div>
    </div>
</div>
