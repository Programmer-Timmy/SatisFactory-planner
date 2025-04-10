<?php
$user = Users::getUserById($_SESSION['userId']);
global $changelog;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['username']) && isset($_POST['email'])) {
        $result = Users::handleProfileUpdate($user);
        if (isset($result['error'])) {
            $_SESSION['error'] = $result['error']; // Store error in session
        } elseif ($result['success']) {
            $_SESSION['success'] = 'Profile updated successfully!';
            header('Location: /account');
            exit;
        }
    }

    if (isset($_POST['password']) && isset($_POST['password2'])) {
        $result = Users::handlePasswordUpdate($user);
        if (isset($result['error'])) {
            $_SESSION['error'] = $result['error']; // Store error in session
        } elseif ($result['success']) {
            $_SESSION['success'] = 'Password updated successfully. Please log in again.';
            $_SESSION['userId'] = null;
            $_SESSION['redirect'] = '/account';
            header('Location: /login');
            exit;
        }
    }
}

if (isset($_GET['delete'])) {
    Users::deleteUser($user->id);
    $_SESSION['userId'] = null;
    $_SESSION['redirect'] = '/account';
    header('Location: /login');
}

$loginAttempts = AuthControler::getLoginAttempts($user->id);
$errorLogs = ErrorHandeler::getUserErrorLogs($user->id);
?>

<div class="container">
    <?php GlobalUtility::displayFlashMessages(); ?>

    <h1 class="mb-3">Welcome, <?= htmlspecialchars($user->username) ?>!</h1>
    <!-- user info change card -->
    <div class="row">
        <div class="col-12">
            <form method="post">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa-solid fa-user-cog text-primary me-2"></i>Account Information
                        </h5>

                        <?php if ($user->email == null) : ?>
                            <div class="alert alert-warning" role="alert">
                                You have not set an email address please update your account info.
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required
                                   maxlength="45"
                                   value="<?= htmlspecialchars($_POST['username'] ?? $user->username) ?>"
                                   autocomplete="username">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?= htmlspecialchars($_POST['email'] ?? $user->email) ?>"
                                   autocomplete="email">
                        </div>

                        <div class="mb-3">
                            <label class="form-label me-2" for="updates">Receive Updates</label>
                            <input type="checkbox" name="updates" data-toggle="toggle" data-onstyle="success"
                                   data-offstyle="danger" data-size="sm" data-onlabel="Yes" data-offlabel="No"
                                <?= isset($_POST['updates']) || $user->updates ? 'checked' : '' ?>>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Account</button>
                        <a href="/account" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Connected accounts -->
    <div class="row">
        <div class="col-12">
            <div class="card mt-3 shadow-sm">
                <div class="card-body p-3">
                    <h5 class="card-title mb-2"><i class="fa-solid fa-link text-primary me-2"></i>Connected Accounts
                    </h5>
                    <div class="border border-light shadow-sm rounded p-2">
                        <div class="d-flex align-items-center mb-2">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Google_%22G%22_logo.svg/800px-Google_%22G%22_logo.svg.png"
                                 alt="Google Logo" width="20" height="20" class="me-2">
                            <strong class="small">Google</strong>
                        </div>
                        <?php if ($user->google_id) : ?>
                            <p class="mb-2 text-success small">Connected
                                - <?= htmlspecialchars($user->google_email) ?></p>
                            <a href="/login/google-oauth/disconnect"
                               class="btn btn-outline-danger btn-sm">Disconnect</a>
                        <?php else : ?>
                            <a href="/login/google-oauth" class="btn btn-outline-primary btn-sm">Connect Google</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Change Password form -->
    <form method="post">
        <!-- Password change card -->
        <div class="row">
            <div class="col-12">
                <div class="card mt-4 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa-solid fa-key text-primary me-2"></i>Change Password</h5>
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" required
                                   autocomplete="new-password">
                        </div>
                        <div class="mb-3">
                            <label for="password2" class="form-label">Repeat New Password</label>
                            <input type="password" class="form-control" id="password2" name="password2" required
                                   autocomplete="off">
                        </div>
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <!--    latest login attmepts-->
    <div class="row">
        <div class="col-12">
            <div class="card mt-3 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-2"><i class="fa-solid fa-user-lock text-primary me-2"></i>Latest Login
                        Attempts</h5>
                    <?php if (empty($loginAttempts)) : ?>
                        <div class="alert alert-info" role="alert">
                            No login attempts have been made.
                        </div>
                    <?php else : ?>

                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th scope="col">Date</th>
                                <th scope="col">IP Address</th>
                                <th scope="col">Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach (array_slice($loginAttempts, 0, 5) as $attempt) : ?>
                                <tr>
                                    <td><?= $attempt->login_timestamp ?></td>
                                    <td><?= $attempt->ip_address ?></td>
                                    <td><?= $attempt->success ? 'Success' : 'Failed' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- your saved data (data we have saved about you) -->
    <div class="row">
        <div class="col-12">
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fa-solid fa-user-shield text-primary me-2"></i> Privacy & Data Storage
                    </h5>

                    <p class="card-text text-muted">
                        We only store the necessary data to manage your account. No personal data is shared or sold.
                    </p>

                    <ul class="list-group list-group-flush rounded">
                        <?php if ($loginAttempts) : ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <i class="fa-solid fa-key text-warning me-2"></i> Login Attempts
                                <span class="badge bg-warning rounded-pill text-black"><?= count($loginAttempts) ?></span>
                            </li>
                        <?php endif; ?>

                        <?php if ($errorLogs) : ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <i class="fa-solid fa-bug text-danger me-2"></i> Error Logs
                                <span class="badge bg-danger rounded-pill text-black"><?= count($errorLogs) ?></span>
                            </li>
                        <?php endif; ?>

                        <?php if ($user->google_id) : ?>
                            <li class="list-group-item">
                                <i class="fa-brands fa-google text-primary me-2"></i> Your Gmail and Google ID are stored.
                            </li>
                        <?php endif; ?>

                        <li class="list-group-item">
                            <i class="fa-solid fa-user text-secondary me-2"></i> Username, email, password, and update preferences are stored.
                        </li>
                    </ul>

                    <div class="mt-3 d-flex gap-2">
                        <a href="/privacy" class="btn btn-outline-primary">
                            <i class="fa-solid fa-file-shield me-1"></i> Privacy Policy
                        </a>
                        <a href="account/delete-data" class="btn btn-outline-danger">
                            <i class="fa-solid fa-trash me-1"></i> Delete Personal Data
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>


    <!-- Delete Account button -->
    <div class="row">
        <div class="col-12">
            <a href="/account?delete" class="btn btn-danger mt-3"
               onclick="return confirm('Are you sure you want to delete your account?')">Delete Account</a>
        </div>
    </div>
</div>
