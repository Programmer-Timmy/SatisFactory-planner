<?php
$error = null;
if ($_POST) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password2 = $_POST['password2'];

    // Check username and email length
    if (strlen($username) > 45) {
        $error = 'Username exceeds the maximum allowed length. Please use up to 45 characters.';
    } elseif (strlen($email) > 200) {
        $error = 'Email is too lengthy. Please use an email under 200 characters.';
    } elseif ($username !== strip_tags($username)) {
        $error = 'Security Alert: Unauthorized characters detected in username. Nice try, but FICSIT Security has blocked that!';
    } elseif ($email !== strip_tags($email)) {
        $error = 'Security Alert: The email contains restricted characters. FICSIT’s defense matrix denies entry!';
    } elseif (strlen($password) < 8) {
        $error = 'Password is too short. Use a minimum of 8 characters for enhanced security.';
    } elseif ($password !== $password2) {
        $error = 'Passwords do not match. Please double-check and try again.';
    }

    // Proceed if no errors
    if (!$error) {
        // Sanitize username and email
        $username = htmlspecialchars($username);
        $email = htmlspecialchars($email);

        // Check for existing username or email
        if (Users::getUserByUsername($username)) {
            $error = 'The username is already taken. Please choose another.';
        } elseif (Users::getUserByEmail($email)) {
            $error = 'This email is already registered. Use a different email or login.';
        } else {
            // Attempt to create user
            if (Users::createUser($username, $password, $email)) {
                $_SESSION['userId'] = null;
                $_SESSION['redirect'] = '/account';
                header("Location: /login/verify?registered=$username");
                exit(); // Stops execution after redirection
            } else {
                $error = 'An error occurred while creating your account. Please try again or contact support.';
            }
        }
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
                            <input type="text" class="form-control" id="username" name="username" required
                                   maxlength="45"
                                   autocomplete="username"
                                   value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required maxlength="200"
                                   autocomplete="email"
                                   value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required
                                   minlength="8"
                                   autocomplete="new-password">
                        </div>
                        <div class="mb-3">
                            <label for="password2" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="password2" name="password2" required
                                   minlength="8"
                                   autocomplete="off">
                        </div>
                        <p class="small text-muted">By clicking Register, you agree to our <a href="/privacy">Privacy Policy</a>.</p>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">Register</button>
                            <a href="/login" class="btn btn-secondary">Go back</a>
                        </div>
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
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
