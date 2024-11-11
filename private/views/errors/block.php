<?php
http_response_code(403);
$blocked = Database::query("SELECT * FROM blocked_ips where ip_address = ?", [$_SERVER['REMOTE_ADDR']]);
if ($blocked) {
    $blocked = $blocked[0];
    $blocked->blocked_until = GlobalUtility::dateTimeToLocal($blocked->blocked_until);
}
$env = parse_ini_file(__DIR__ . '/../../../.env');
$ipInfoKey = $env['IP_INFO_TOKEN'];
$ip = $_SERVER['REMOTE_ADDR'];

$details = json_decode(file_get_contents("https://vpnapi.io/api/$ip?key=$ipInfoKey"));

$isVpn = $details->security->vpn;
$isProxy = $details->security->proxy;
?>

<style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
    }

    .container {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background-color: #2d2d30;
        color: #e8e8e8;
    }

    .access-denied-card {
        background-color: #333;
        border: 2px solid #FF8C00;
        border-radius: 8px;
        padding: 2rem;
        width: 100%;
        max-width: 600px;
        text-align: center;
        box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.5);
    }

    .access-denied-card h1 {
        color: #FF8C00;
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 1rem;
    }

    .access-denied-card p {
        margin: 0.5rem 0;
        font-size: 1.1rem;
    }

    .highlight {
        color: #FF8C00;
        font-weight: bold;
    }

    .details {
        font-size: 0.9rem;
        color: #CCCCCC;
    }

    .alert-box {
        background-color: #444;
        border: 2px solid #FF8C00;
        border-radius: 5px;
        padding: 1rem;
        margin: 1rem 0;
        text-align: left;
    }

    .alert-box .alert-title {
        font-weight: bold;
        color: #FF8C00;
        font-size: 1.2rem;
        margin-bottom: 0.5rem;
    }

    .alert-box .alert-message {
        color: #e8e8e8;
        font-size: 1rem;
    }
</style>

<div class="container">
    <div class="access-denied-card">
        <h1>Access Denied</h1>
        <p>Your IP address has been <span class="highlight">blocked</span> from accessing this site.</p>
        <p>Please contact the site administrator for more information.</p>

        <?php if ($blocked) : ?>
            <p class="details">You are blocked until: <span class="highlight"><?= $blocked->blocked_until ?></span></p>
            <p class="details">Reason: <span class="highlight"><?= $blocked->reason ?></span></p>
        <?php endif; ?>

        <?php if ($isVpn) : ?>
            <div class="alert-box">
                <div class="alert-title">⚠️ VPN Detected</div>
                <div class="alert-message">Your IP address appears to be using a <span class="highlight">VPN</span>.
                    Access may be restricted due to security policies.
                </div>
            </div>
        <?php endif; ?>

        <?php if ($isProxy) : ?>
            <div class="alert-box">
                <div class="alert-title">⚠️ Proxy Detected</div>
                <div class="alert-message">Your IP address appears to be using a <span class="highlight">Proxy</span>.
                    Access may be restricted due to security policies.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
