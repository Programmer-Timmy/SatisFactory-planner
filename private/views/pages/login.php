<?php
$error = '';
$success = '';
if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $data = AuthControler::login($username, $password);
    if ($data != null) {
        if (is_array($data)) {
            if ($data[1] == 'maxAttempts') {
                $error = 'You have reached the maximum login attempts. Please try again later.';
            } elseif ($data[1] == 'notVerified') {
                $error = 'Email not verified please check your email. If you did not receive an email please check your spam folder. <a href="/login/verify?resend=' . $data[0] . '">Resend verification email</a>';
            }
        } else {
            header('Location: ' . $data);
        }
    } else {
        $error = 'Username or password is incorrect';
    }
}

if (isset($_GET['verify']) && strtok($_SERVER['REQUEST_URI'], '?') == '/login') {
    $error = 'You used the old verification system. Login and click the "Resend Verification Email" link to get a new verification email.';
}
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="text-center mb-4">
                <h3 class="title">Login</h3>
            </div>
            <?php if ($error) : ?>
                <div class="alert alert-danger" role="alert">
                    <?= $error ?>
                </div>
            <?php endif; ?>
            <?php if ($success) : ?>
                <div class="alert alert-success" role="alert">
                    <?= $success ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['registered'])) : ?>
                <?php $username = $_GET['registered'] ?>
                <div class="alert alert-success" role="alert">
                    You have successfully registered! Please check your email to verify your account. If you don't see
                    the email, check your spam folder. Didn't receive it? <a href='?resent=<?= $username ?>'>Resend the
                        verification email</a>.
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['logout'])) : ?>
                <div class="alert alert-success" role="alert">
                    You have successfully logged out.
                </div>
            <?php endif; ?>
            <div class="card">
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required
                                   autocomplete="username">
                        </div>
                        <div class="mb-3">
                            <!--                            auto complete-->
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required
                                   autocomplete="current-password">
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">Login</button>
                            <a href="/register" class="btn btn-secondary">Register</a>
                        </div>
                    </form>
                    <div class="mt-3">
                        <div style="display: flex; align-items: center; text-align: center;">
                            <hr style="flex: 1; border: none; border-top: 1px solid #ccc;">
                            <span style="padding: 0 10px; color: #666;">or</span>
                            <hr style="flex: 1; border: none; border-top: 1px solid #ccc;">
                        </div>
                        <div class="d-flex justify-content-center">
                            <a href="/login/google-oauth" class=""><img
                                        src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Google_%22G%22_logo.svg/800px-Google_%22G%22_logo.svg.png"
                                        alt="Google" style="width: 40px; height: 40px;"></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if (strtok($_SERVER['REQUEST_URI'], '?') == '/login'): ?>
    <script>
        const url = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({path: url}, "", url);
    </script>
<?php endif; ?>
