<?php
$error = null;
if ($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $password2 = $_POST['password2'];
    if ($password == $password2) {
        if (Users::getUserByUsername($username)) {
            $error = 'Username already exists';
        } else {
            if (Users::createUser($username, $password)) {
                $_SESSION['userId'] = null;
                $_SESSION['redirect'] = '/account';
                header('Location: /login');
            } else {
                $error = 'Error creating user';
            }
        }
    } else {
        $error = 'Passwords do not match';

    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="text-center mb-4">
                <h3 class="title">Register</h3>
            </div>
            <?php if ($error) : ?>
                <div class="alert alert-danger" role="alert">
                    <?= $error ?>
                </div>
            <?php endif; ?>
            <div class="card">
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="password2" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="password2" name="password2" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Register</button>
                        <a href="/login" class="btn btn-secondary">Go back</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
