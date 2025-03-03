<?php
$error = false;
global $changelog;

if (isset($_POST['send'])) {
    $users = Users::getAllUsers();
    try {
        foreach ($users as $user) {
            if ($user->updates) {
                Mailer::sendWebsiteUpdateEmail($user);
            }
        }
    } catch (Exception $e) {
        $error = 'An error occurred while trying to send the emails.';
    }
}
?>

<div class="container">
    <h1 class="text-center mb-4">Send Update Emails</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger text-center" role="alert">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <p class="text-center fs-5">
        Are you sure you want to send update emails to all users who have updates enabled?
    </p>

    <div class="d-flex justify-content-center mt-4">
        <form method="post" class="d-inline">
            <input type="hidden" name="id" value="It is so nice that a post needs data to be sent :)">
            <button type="submit" class="btn btn-primary me-3 px-4" name="send">Send Emails</button>
        </form>
        <a href="/admin/siteSettings" class="btn btn-secondary px-4">Cancel</a>
    </div>
</div>

