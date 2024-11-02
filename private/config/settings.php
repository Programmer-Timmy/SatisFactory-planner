<?php
/**
 * Database Settings
 */
$database = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'database' => 'satisfactoryplanner',
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
        'email' => 'updates@satisfactoryplanner.timmygamer.nl',
        'name' => 'Satisfactory Planner'
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
    'redirect' => '/login', // redirect to this page if the user is not logged in

    // Admin settings
    'admin' => [
        'enabled' => true,
        'sessionName' => 'admin', // the session name that will be used to store that the user is a admin check by isset function
        'filterInUrl' => 'admin', // empty string means no filter
        'redirect' => '/login', // redirect to this page if the user is not a admin
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
    'register' => 'Register - ' . $site['siteName'],
    'account' => 'Account - ' . $site['siteName'],
    'helpfulLinks' => 'Helpful Links - ' . $site['siteName'],
    '404' => '404 - Oops page not found!',
    // Add more titles as needed
];

$description = [
    'default' => 'Satisfactory Planner is a site that helps you plan and organize your factories in the game Satisfactory.',
    'maintenance' => 'The site is currently under maintenance, please check back later.',
    'home' => 'Satisfactory Planner is a site that helps you plan and organize your factories in the game Satisfactory.',
    'about' => 'Learn more about Satisfactory Planner and the team behind it.',
    'contact' => 'Contact the team behind Satisfactory Planner.',
    'game_save' => 'View and edit your game saves.',
    'production_line' => 'View and edit your production lines.',
    'login' => 'Login to your account.',
    'register' => 'Register for an account.',
    'account' => 'View and edit your account settings.',
    'helpfulLinks' => 'A collection of helpful links for Satisfactory.',
    '404' => 'Oops page not found!',
    // Add more descriptions as needed
];

$keywords = [
    'default' => 'Satisfactory, Satisfactory Planner, Factory Planner, Automation Game, Base Building, Simulation Game',
    'home' => 'Satisfactory Planner Home, Factory Management, Satisfactory Guide, Satisfactory Planning Tools',
    'about' => 'About Satisfactory Planner, Factory Simulation, Game Planning Tools, Base Building Guide',
    'contact' => 'Contact Satisfactory Planner, Game Support, Factory Planning Assistance, Community Help',
    'game_save' => 'Satisfactory Game Save, Factory Save Manager, Save Game Edit, Game Save Management',
    'production_line' => 'Satisfactory Production Line, Factory Efficiency, Production Planning, Resource Management',
    'login' => 'Satisfactory Planner Login, Access Factory Planner, Game Save Login, Satisfactory Account',
    'register' => 'Register Satisfactory Planner, Create Account, Join Factory Planner, Sign Up',
    'account' => 'Account Settings, Satisfactory Planner Account, Manage Satisfactory Profile, Game Save Preferences',
    'helpfulLinks' => 'Satisfactory Resources, Game Links, Factory Planning Guides, Satisfactory Tutorials',
    '404' => 'Page Not Found, Satisfactory Planner Error, Factory Planner 404, Broken Link',
    // Add more specific keywords for other pages
];


// Disable errors if debug is set to false
if (!$site['debug']) {
    error_reporting(0);

}else{
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}