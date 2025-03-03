<?php

if (!isset($_GET['id'])) {
    header('Location: /admin/users');
    exit(0);
}

$user = Users::getUserById($_GET['id']);

if (!$user) {
    header('Location: /admin/users');
    exit(0);
}

$error = false;

if ($_POST) {
    $id = $user->id;

    try {
        Users::deleteUser($id);
        header('Location: /admin/users');
        exit(0);
    } catch (Exception $e) {
        $error = 'An error occurred while trying to delete the user.';
    }
}

?>

<div class="container">
    <h1 class="text-center text-danger mb-4">Delete User</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger text-center" role="alert">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <p class="text-center fs-5">
        Are you sure you want to permanently delete the user <strong><?= htmlspecialchars($user->username) ?></strong>?
    </p>

    <div class="d-flex justify-content-center mt-4">
        <form method="post" class="d-inline">
            <input type="hidden" name="id" value="It is so nice that a post needs data to be sent :)">
            <button type="submit" class="btn btn-danger me-3 px-4">Yes, Delete</button>
        </form>
        <a href="/admin/users" class="btn btn-secondary px-4">Cancel</a>
    </div>
</div>

