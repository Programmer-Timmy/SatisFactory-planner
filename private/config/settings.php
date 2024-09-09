<?php
/**
 * Database Settings
 */
$database = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'database' => 'satisfactory_planner',
];

/**
 * email settings
 */
$emailSettings = [
    'host' => '',
    'SMTPAuth' => true,
    'username' => '',
    'password' => '',
    'encryption' => 'tls', // tls or ssl
    'port' => 587, // 587 or 465
    'from' => [
        'email' => '',
        'name' => ''
    ]
];

/**
 * Site Settings
 */
$site = [
    // General settings
    'siteName' => 'Satisfactory Planner',
    'debug' => true, // shows errors if true
    'maintenance' => false, // shows the maintenance page if true the client's IP is not in the allowedIPs array

    // ajax on or off
    'ajax' => true, // if true the site will only load the ajax pages

    // Auth settings
    'user-adminTable' => 'users', // the table name that will be used to check if the user/admin exists
    'saveUrl' => true, // save the url in the session, so you can redirect the user back to the page he was before he logged in
    'redirect' => 'login', // redirect to this page if the user is not logged in

    // Admin settings
    'admin' => [
        'enabled' => false,
        'sessionName' => 'admin', // the session name that will be used to store that the user is a admin check by isset function
        'filterInUrl' => 'admin', // empty string means no filter
    ],

    // Accounts settings
    'accounts'=>[
        'enabled' => true   ,
        'sessionName' => 'userId', // the session name that will be used to store that the user is logged in check by isset function
        'filterInUrl' => '', // empty string means no filter
    ],

    // popup settings
    'showPopup' => true,
    'popupTitle' => 'Welcome to the site!',
    'popupMessage' => 'This is Satisfactory Planner, a site that helps you plan and organize your factories in the game Satisfactory.',
    'popupButtons' => [
        [
            'label' => 'Changelog',
            'action' => '/changelog'
        ],
        // Add more buttons as needed
    ]

];

/**
 * Allowed IPs That can bypass the maintenance
 */
$allowedIPs = ['84.83.150.26']; // ['::0'] means all IPs are allowed

/**
 * Page Title Settings
 */
$url = $_SERVER['REQUEST_URI'];

// If the URL is the root path, set it to '/home'
if ($url == '/') {
    $url = '/home';
}

$titles = [
    'default' => substr($url, 1) . ' - ' . $site['siteName'],
    'maintenance' => 'Under Maintenance - ' . $site['siteName'],
    'home' => 'Home - ' . $site['siteName'],
    'about' => 'About Us - ' . $site['siteName'],
    'contact' => 'Contact Us - ' . $site['siteName'],
    'game_save' => 'Game Save - ' . $site['siteName'],
    'production_line' => 'Production Line - ' . $site['siteName'],
    'login' => 'Login - ' . $site['siteName'],
    '404' => '404 - Oops page not found!',
    // Add more titles as needed
];

// Disable errors if debug is set to false
if (!$site['debug']) {
    error_reporting(0);

}else{
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}