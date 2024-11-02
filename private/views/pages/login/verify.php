<?php
$userName = urldecode($_GET['usr'] ?? '');
$email = urldecode($_GET['eml'] ?? '');
$token = urldecode($_GET['tkn'] ?? '');
$resend = urldecode($_GET['resend'] ?? '');
$registered = urldecode($_GET['registered'] ?? '');

$error = '';
$success = '';
$verified = false;

if (isset($_POST['token'])) {
    if (Users::verifyUser($_POST['token'])) {
        $verified = true;
    } else {
        $error = 'The verification code is invalid. Please check your latest email for the correct link. If you did not receive an email, please check your spam folder. To resend the email, log in and click the "Resend Verification Email" link.';
    }
}

if (!($userName && $email && $token) && !$resend && !$registered) {
    header('Location: /login');
    exit();
}

if ($resend) {
    if (Users::checkIfValidated($resend)) {
        $error = 'Email already verified';
    } else {
        if (Users::resendVerificationEmail($resend)) {
            $success = 'Verification email resent successfully. Please check your email. If you did not receive an email, please check your spam folder. If you really did not receive an email, you can try to resend the verification email by clicking the button below.';
        } else {
            $error = 'Error resending verification email';
        }
    }
}

if ($registered) {
    $success = 'Registration successful! Please check your email for the verification link. If you did not receive an email, please check your spam folder. If you really did not receive an email, you can try to resend the verification email by clicking the button below.';
    $resend = $registered;
}

if ($userName && $email && $token) {
    $verificationStatus = Users::CheckVerificationStatus($userName, $email, $token);

    if (isset($verificationStatus['error_code']) && $verificationStatus['error_code'] === 1) {
        header('Location: /login');
        exit();
    }
}

$verificationStatus = Users::CheckVerificationStatus($userName, $email, $token);
?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="text-center mb-4">
                <h1 class="">Email Verification</h1>
                <p class="lead">To continue, please verify your email address.</p>
            </div>
            <?php if ($success): ?>
                <div class="card border-success mb-3">
                    <div class="card-body text-center">
                        <h5 class="card-title">Success!</h5>
                        <p class="card-text"><?= $success ?></p>

                        <?php if ($resend): ?>
                            <a href="/login/verify?resend=<?= htmlspecialchars($resend) ?>" class="btn btn-primary">Resend
                                Verification Email</a>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endif; ?>

            <!-- Show Go to Login button if verified -->
            <?php if ($verified): ?>
                <div class="text-center mb-3 card border-success">
                    <div class="card-body">
                        <h5 class="card-title">Email Verified!</h5>
                        <p class="card-text">Your email has been successfully verified.</p>
                        <a href="/login" class="btn btn-success">Proceed to Login</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Show verification or resend option based on status -->
            <?php if (isset($verificationStatus['error_code']) && $verificationStatus['error_code'] === 3 && !$verified): ?>
                <div class="text-center mb-3 card border-danger">
                    <div class="card-body">
                        <h5 class="card-title text-danger">Invalid Token</h5>
                        <p class="card-text">The verification link is invalid. Please check your email for the correct
                            link or resend it.</p>
                        <a href="?resent=<?= htmlspecialchars($userName) ?>" class="btn btn-danger">Resend Verification
                            Email</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($verificationStatus['error_code']) && $verificationStatus['error_code'] === 2 && !$verified): ?>
                <div class="text-center mb-3 card border-success">
                    <div class="card-body">
                        <h5 class="card-title">Your Email is Already Verified</h5>
                        <p class="card-text">You can now Log in using the button below.</p>
                        <a href="/login" class="btn btn-success">Go to Login</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!isset($verificationStatus['error_code']) && !$verified && $userName && $email && $token): ?>
                <div class="text-center mb-3 card border-info">
                    <div class="card-body">
                        <h5 class="card-title">Verify Your Email</h5>
                        <p class="card-text">To complete the registration, please click the button below.</p>
                        <form method="post"
                              action="/login/verify?usr=<?= htmlspecialchars($userName) ?>&eml=<?= htmlspecialchars($email) ?>&tkn=<?= htmlspecialchars($token) ?>">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            <button type="submit" class="btn btn-info">Verify Email</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const url = window.location.protocol + "//" + window.location.host + window.location.pathname;
    window.history.replaceState({path: url}, "", url);
</script>