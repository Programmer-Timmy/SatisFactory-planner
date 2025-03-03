<?php

// Initialize the session
session_start();
// Update the following variables
$google_oauth_client_id = '';
$google_oauth_client_secret = '';
$google_oauth_redirect_uri = 'http://sataisfactoryplanner.nl/login/google-oauth';
$google_oauth_version = 'v3';

// If the captured code param exists and is valid
if (isset($_GET['code']) && !empty($_GET['code'])) {
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
    echo '<pre>';
    var_dump($response);
    echo '</pre>';
    // Code goes here...

    if (!isset($response['access_token'])) {
        die('Fout: Geen access token ontvangen');
    }

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
    echo '<pre>';
    var_dump($userinfo);
    echo '</pre>';

} else {
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