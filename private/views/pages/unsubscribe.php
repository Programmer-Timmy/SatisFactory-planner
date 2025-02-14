<?php

if (!isset($_GET['email'])) {
    header('Location: /');
    exit(0);
}

$email = $_GET['email'];
if (!Users::unsubscribeUser($email)) {
    $message = 'An error occurred while trying to unsubscribe.';
}


?>

<div class="container">
    <h1 class="text-center text-danger mb-4">Unsubscribe</h1>

    <?php if (isset($message)): ?>
        <div class="alert alert-danger text-center" role="alert">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <p class="text-center fs-5">
        You have been successfully unsubscribed from all emails.
    </p>

    <div class="d-flex justify-content-center mt-4">
        <a href="/" class="btn btn-primary me-3 px-4">Return to Home</a>
    </div>
</div>

