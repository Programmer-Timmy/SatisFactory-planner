<?php
if (!isset($_SESSION['userId']) && !$_SESSION['userId']) {
    header('Location: /login');
    exit();
}

if ($_POST && isset($_POST['disconnect'])) {
    Users::disconnectGoogleAccount($_SESSION['userId']);
    header('Location: /account');
    exit();
}
?>

<div class="container">
    <h1 class="text-center text-danger mb-4">Disconnect Google Account</h1>

    <p class="text-center fs-5">
        Are you sure you want to disconnect your Google account?
    </p>

    <div class="d-flex justify-content-center mt-4">
        <form method="post">
            <button type="submit" value="disconnect" name="disconnect" class="btn btn-danger me-3 px-4">Disconnect
            </button>
        </form>
        <a href="/account" class="btn btn-primary me-3 px-4">Cancel</a>
    </div>
</div>
