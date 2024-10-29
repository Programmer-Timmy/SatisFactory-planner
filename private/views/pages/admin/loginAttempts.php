<?php

$loginAttempts = AuthControler::getAllLoginAttempts();

foreach ($loginAttempts as $loginAttempt) {
    $loginAttempt->login_timestamp = GlobalUtility::dateTimeToLocal($loginAttempt->login_timestamp);
}
?>

<div class="container">
    <h1 class="text-center mb-4">Login Attempts</h1>
    <a href="/admin" class="btn btn-primary w-100 mb-3">Return to admin page</a>
    <?php if (empty($loginAttempts)): ?>
        <div class="alert alert-info text-center" role="alert">
            No login attempts have been made.
        </div>
    <?php else: ?>
        <?= GlobalUtility::createTable($loginAttempts, ['username', 'ip_address', 'success', 'login_timestamp']) ?>
    <?php endif; ?>
</div>