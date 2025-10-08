<?php

// Update the following variables
$existingUser = null;
$error = null;
$showFullScreenError = false;

// load .env file
$env = parse_ini_file(__DIR__ . '../../../../../../.env');

$google_oauth_client_id = $env['GOOGLE_OAUTH_CLIENT_ID'];
$google_oauth_client_secret = $env['GOOGLE_OAUTH_CLIENT_SECRET'];
$google_oauth_redirect_uri = 'https://satisfactoryplanner.timmygamer.nl/login/google-oauth';
$google_oauth_version = 'v3';

if (isset($_POST['type'])) {
    if ($_POST['type'] === 'linkGoogle') {
        $redirect = Users::linkGoogleAccount($_POST['googleId'], $_POST['email'], $_POST['password']);
        if ($redirect) {
            header('Location: https://satisfactoryplanner.timmygamer.nl/' . $redirect);
            exit;
        }
    }

    if ($_POST['type'] === 'createUser') {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $googleId = $_POST['googleId'];
        $password = $_POST['password'];

        if (Users::getUserByUsername($username)) {
            $error = 'Username already exists';
        } elseif (Users::getUserByEmail($email)) {
            $error = 'Email already exists';
        } else {
            try {
                $user_id = Users::createUser($username, $password, $email, $googleId, $email);
                header("Location: /login/verify?registered=$username");

            } catch (Exception $e) {
                $error = 'An error occurred while creating your account. Please try again or contact support.';

            }
        }
    }
}

// If the captured code param exists and is valid
if (isset($_GET['code']) && !empty($_GET['code']) && !isset($_POST['type'])) {
    // Execute cURL request to retrieve the access token
    $params = [
        'code' => $_GET['code'],
        'client_id' => $google_oauth_client_id,
        'client_secret' => $google_oauth_client_secret,
        'redirect_uri' => $google_oauth_redirect_uri,
        'grant_type' => 'authorization_code'
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://accounts.google.com/o/oauth2/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//    disable ssl
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    if ($response === false) {
        echo 'cURL Error: ' . curl_error($ch);
    }

    curl_close($ch);
    $response = json_decode($response, true);
    // Code goes here...
    if (!isset($response['access_token'])) {
        $error = 'An error occurred while retrieving the access token';
    } else {

        $access_token = $response['access_token'];

// Vraag de gebruikersgegevens op bij de Google API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v1/userinfo?alt=json');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
//    disable ssl
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $userinfo = curl_exec($ch);
        if ($userinfo === false) {
            echo 'cURL Error: ' . curl_error($ch);
        }
        curl_close($ch);

        $userinfo = json_decode($userinfo, true);

        $connectedAccount = Users::getGoogleConnectedUser($userinfo['id']);
        $existingUser = Users::getUserByEmail($userinfo['email']);

        if ($connectedAccount) {
            $data = AuthControler::loginGoogleSSO($userinfo['id']);
            if ($data !== null && !is_array($data)) {
                header('Location: https://satisfactoryplanner.timmygamer.nl/' . $data);
                exit;
            } elseif (is_array($data)) {
                if ($data[1] == 'maxAttempts') {
                    $showFullScreenError = true;
                    $error = 'You have reached the maximum login attempts. Please try again later.';
                } elseif ($data[1] == 'notVerified') {
                    $showFullScreenError = true;
                    $error = 'Email not verified please check your email. If you did not receive an email please check your spam folder. <a href="/login/verify?resend=' . $data[0] . '">Resend verification email</a>';
                }
            }
        } elseif ($_SESSION['userId']) {
            Users::linkGoogleAccountBySession($userinfo['id'], $userinfo['email']);
            header('Location: https://satisfactoryplanner.timmygamer.nl/account');
        }

    }
} elseif (!isset($_POST['type']) && !isset($_GET['code'])) {
    // Define params and redirect to Google Authentication page
    $params = [
        'response_type' => 'code',
        'client_id' => $google_oauth_client_id,
        'redirect_uri' => $google_oauth_redirect_uri,
        'scope' => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
        'access_type' => 'offline',
        'prompt' => 'consent'
    ];
    header('Location: https://accounts.google.com/o/oauth2/auth?' . http_build_query($params));
    exit;
}
?>

<div class="container mt-5 ">

    <?php if ($existingUser && !$error) : ?>
        <div class="d-flex justify-content-center">

            <div class="col-md-6">
                <div class="card shadow-sm p-4">
                    <h1 class="mb-3">Welcome back, <?= htmlspecialchars($existingUser->username) ?>!</h1>
                    <p>Do you want to link your Google account to your existing account?</p>

                    <form method="post" class="mb-3">
                        <input type="hidden" name="type" value="linkGoogle">
                        <input type="hidden" name="googleId" value="<?= htmlspecialchars($userinfo['id']) ?>">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($userinfo['email']) ?>">

                        <div class="mb-3">
                            <label for="password" class="form-label">Enter your password (Only when linking
                                accounts):</label>
                            <input type="password" id="password" name="password" class="form-control"
                                   placeholder="Password"
                                   required>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Yes, link account</button>
                            <a href="/login" class="btn btn-secondary">No, return to login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php elseif (isset($userinfo) && !$showFullScreenError) : ?>
        <div class="d-flex justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm p-4">
                    <h1 class="mb-3">Welcome, <?= htmlspecialchars($userinfo['name']) ?>!</h1>
                    <p>Do you want to create an account with the following details?</p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($userinfo['email']) ?></p>

                    <form method="post" class="mb-3">
                        <?php if ($error) : ?>
                            <div class="alert alert-danger" role="alert">
                                <?= $error ?>
                            </div>
                        <?php endif; ?>
                        <input type="hidden" name="type" value="createUser">
                        <input type="hidden" name="googleId" value="<?= htmlspecialchars($userinfo['id']) ?>">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($userinfo['email']) ?>">

                        <div class="mb-3">
                            <label for="username" class="form-label">Choose a username:</label>
                            <input type="text" id="username" name="username" class="form-control" placeholder="Username"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Choose a password:</label>
                            <input type="password" id="password" name="password" class="form-control"
                                   placeholder="Password"
                                   required>
                        </div>

                        <div class="mb-3 pb-2">
                            <label for="password" class="form-label">Confirm your password:</label>
                            <input type="password" id="password" name="password" class="form-control"
                                   placeholder="Password"
                                   required>
                        </div>
                        <p class="small text-muted">By clicking create account, you agree to our <a href="/privacy">Privacy
                                Policy</a>.</p>


                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Yes, create account</button>
                            <a href="/login" class="btn btn-secondary">No, return to login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php elseif ($error) : ?>
        <div class="alert alert-danger mb-0" role="alert">
            <?= $error ?>
        </div>
    <?php else : ?>
        <div class="alert alert-danger mb-0" role="alert">
            An error occurred while trying to authenticate with Google. Please try again.
        </div>
    <?php endif; ?>

</div>
