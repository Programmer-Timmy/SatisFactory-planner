<?php
$user = Users::getUserById($_SESSION['userId']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['username'])) {
        $username = $_POST['username'];
        if (Users::getUserByUsername($username)) {
            $error = 'Username already exists';
        } else {
            if (Users::updateUsername($user->id, $username)){
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
    <!-- Change Username form -->
    <form method="post">
        <!-- Username change card -->
        <div class="row">
            <div class="col-12">
                <div class="card mt-3">
                    <div class="card-body">
                        <h5 class="card-title">Change Username</h5>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?= $user->username ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Change Username</button>
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
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="password2" class="form-label">Repeat Password</label>
                            <input type="password" class="form-control" id="password2" name="password2" required>
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
