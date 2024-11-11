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
    $username = $_POST['username'];
    $email = $_POST['email'];
    $updates = isset($_POST['updates']) ? 1 : 0;
    $admin = isset($_POST['admin']) ? 1 : 0;
    $verified = isset($_POST['verified']) ? 1 : 0;

    var_dump($username, $email, $updates, $admin, $verified);

    if (Users::getUserByUsername($username) && $username !== $user->username) {
        $error = 'Username already exists';
    } elseif (Users::getUserByEmail($email) && $email !== $user->email) {
        $error = 'Email already exists';
    } else {
        try {
            Users::updateUserAdmin($user->id, $username, $email, $updates, $admin, $verified);
            header('Location: /admin/users');
            exit();
        } catch (Exception $e) {
            $error = 'An error occurred';
        }
    }
}

?>

<div class="container w-100">
    <div class="row align-items-center mb-3">
        <div class="col-lg-4"></div>
        <div class="col-lg-4">
            <h1 class="text-center">Edit User</h1>
        </div>
        <div class="col-lg-4 text-lg-end text-center">
            <a href="/admin/users" class="btn btn-primary">Return to users page</a>
        </div>
    </div>

    <form method="post">
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?= $error ?>
            </div>
        <?php endif; ?>
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" class="form-control" name="username" value="<?= $user->username ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" name="email" value="<?= $user->email ?>" required>
        </div>
        <div class="form-group pt-2 row d-flex align-items-center">
            <label for="updates" class="col-6 col-md-2">Updates</label>
            <input type="checkbox" class="form-control" name="updates" data-onstyle="success"
                   data-on="Yes" data-off="No" data-offstyle="danger"
                   data-toggle="toggle" <?= $user->updates ? 'checked' : '' ?>>
        </div>
        <div class="form-group pt-2 row d-flex align-items-center">
            <label for="admin" class="col-6 col-md-2">Admin</label>
            <input type="checkbox" class="form-control" name="admin" data-onstyle="success"
                   data-on="Yes" data-off="No" data-offstyle="danger"
                   data-toggle="toggle" <?= $user->admin ? 'checked' : '' ?>>
        </div>
        <div class="form-group pt-2 row d-flex align-items-center">
            <label for="verified" class="col-6 col-md-2">Verified</label>
            <input type="checkbox" class="form-control col" name="verified" data-onstyle="success"
                   data-on="Yes" data-off="No" data-offstyle="danger"
                   data-toggle="toggle" <?= $user->verified ? 'checked' : '' ?>>
        </div>
        <div class="text-center">
            <button type="submit" class="btn btn-primary mt-3">Update User</button>
        </div>
    </form>
</div>
