<?php
$user = Users::getUserById($_SESSION['userId']);
$error = '';
global $changelog;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['username']) && isset($_POST['email']) !== null) {
        if (Users::getUserByEmail($_POST['email']) && $_POST['email'] != $user->email) {
            $error = 'Email already in use';
        } elseif (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) === false) {
            $error = 'Invalid email';
        } elseif (Users::getUserByUsername($_POST['username']) && $_POST['username'] != $user->username) {
            $error = 'Username already in use';
            return;
        } else {
            $username = $_POST['username'];
            if (Users::updateUser($user->id, $username, $_POST['email'], isset($_POST['updates']) ? 1 : 0)) {
                header('Location: /account');
            } else {
                $error = 'Error updating username';
            }
        }
    }
    if (isset($_POST['password']) && isset($_POST['password2'])) {
        $password = $_POST['password'];
        $password2 = $_POST['password2'];
        if ($password == $password2) {
            if (Users::updatePassword($user->id, $password)) {
                $_SESSION['userId'] = null;
                $_SESSION['redirect'] = '/account';
                header('Location: /login');

            } else {
                $error = 'Error updating password';
            }
        } else {
            $error = 'Passwords do not match';
        }
    }
}

if (isset($_GET['delete'])) {
    Users::deleteUser($user->id);
    $_SESSION['userId'] = null;
    $_SESSION['redirect'] = '/account';
    header('Location: /login');
}
?>

<div class="container">
    <?php if ($error) : ?>
        <div class="alert alert-danger" role="alert">
            <?= $error ?>
        </div>
    <?php endif; ?>
    <h1>Account</h1>
    <!-- Change user info form -->
    <form method="post">
        <!-- user info change card -->
        <div class="row">
            <div class="col-12">
                <?php if ($user->email == null) : ?>
                    <div class="alert alert-warning" role="alert">
                        You have not set an email address please update your account info.
                    </div>
                <?php endif; ?>
                <div class="card mt-3">
                    <div class="card-body">
                        <h5 class="card-title">Change User Info</h5>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username"
                                   value="<?= $user->username ?>" required autocomplete="username">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= $user->email ?>"
                                   required autocomplete="email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label me-2" for="updates">Recieve updates</label>
                            <input type="checkbox" name="updates" data-toggle="toggle" data-onstyle="success"
                                   data-offstyle="danger" data-size="sm" data-onlabel="Yes"
                                   data-offlabel="No" <?= $user->updates ? 'checked' : '' ?>>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Account</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <!-- Change Password form -->
    <form method="post">
        <!-- Password change card -->
        <div class="row">
            <div class="col-12">
                <div class="card mt-3">
                    <div class="card-body">
                        <h5 class="card-title">Change Password</h5>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required
                                   autocomplete="new-password">
                        </div>
                        <div class="mb-3">
                            <label for="password2" class="form-label">Repeat Password</label>
                            <input type="password" class="form-control" id="password2" name="password2" required
                                   autocomplete="off">
                        </div>
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <!-- Delete Account button -->
    <div class="row">
        <div class="col-12">
            <a href="/account?delete" class="btn btn-danger mt-3" onclick="return confirm('Are you sure you want to delete your account?')">Delete Account</a>
        </div>
    </div>
</div>
