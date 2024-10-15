<?php

$continue = true;

// Include necessary files
require_once __DIR__ . '/../private/autoload.php';
require_once __DIR__ . '/../private/config/settings.php';

// Start a session
session_start();

// Global variables
global $site;
global $allowedIPs;

if ($site['maintenance'] && !in_array($_SERVER['REMOTE_ADDR'], $allowedIPs)) {
    // Include the maintenance page
    include __DIR__ . '/../private/views/templates/header.php';
    include __DIR__ . '/../private/views/pages/maintenance.php';
    exit();
}

// Determine which page to display based on the request
$requestedPage = $_SERVER['REQUEST_URI'];

if ($requestedPage == "/") {
    $requestedPage = '/home';
}

// remove the get parameters from the url
$position = strpos($requestedPage, "?");
$require = $requestedPage;
if ($position !== false) {
    $newString = substr($requestedPage, 0, $position);
    $require = $newString; // Output: "Hello "
}

// if ajax is enabled and the request is an ajax request load the ajax file
if ($site['ajax']) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        include __DIR__ . "/../private/ajax/$require.php";
        exit();
    }
}

// if url has api in it load the api file and exit
if (str_contains($require, '/api')) {
    if (file_exists(__DIR__ . "/../private/views/pages$require.php")) {
        include __DIR__ . "/../private/views/pages$require.php";
        exit();
    }
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid API endpoint']);
    exit();
}

if ($site['admin']['enabled']) {
    $admin = $site['admin'];
    $pageTemplate = __DIR__ . "/../private/Views/pages$require.php";
    if (file_exists($pageTemplate)) {
        if (str_contains($require, $admin['filterInUrl']) && $require !== $site['redirect'] && $require !== '/404' && $require !== '/maintenance' && $require !== '/changelog') {
            if (!isset($_SESSION[$admin['sessionName']])) {
//                if already logged in show the 403 page
                if (isset($_SESSION[$site['accounts']['sessionName']])) {
                    header('Location: /403');
                    exit();
                }

                if ($site['saveUrl']) {
                    $_SESSION['redirect'] = $requestedPage;
                }
                header('Location:' . $admin['redirect']);
                exit();
            }
        }
    }
}

if ($site['accounts']['enabled']) {
    $accounts = $site['accounts'];

    $pageTemplate = __DIR__ . "/../private/views/pages$require.php";

    if (file_exists($pageTemplate)) {
        if (str_contains($require, $accounts['filterInUrl']) && $require !== $site['redirect'] && $require !== '/404' && $require !== '/maintenance' && $require !== '/register' && $require !== '/changelog') {
            if (!isset($_SESSION[$accounts['sessionName']])) {
                if ($site['saveUrl']) {
                    $_SESSION['redirect'] = $requestedPage;
                }

                $continue = false;
                include __DIR__ . '/../private/views/templates/header.php';
                include __DIR__ . '/../private/views/templates/navbar.php';
                require_once __DIR__ . '/../private/views/pages/login.php';
                include __DIR__ . '/../private/views/templates/footer.php';
            }
        }
    }
}

if ($continue) {
// Include header
    include __DIR__ . '/../private/views/templates/header.php';

// Check if maintenance mode is active and the client's IP is allowed

    // Include the common header
    include __DIR__ . '/../private/views/templates/navbar.php';

    // Determine which page to display based on the request
    $requestedPage = $require;
    if ($requestedPage == "/") {
        $requestedPage = 'home';
    }

    // Include the specific page content
    $pageTemplate = __DIR__ . "/../private/views/pages/$requestedPage.php";

    if (file_exists($pageTemplate)) {
        include $pageTemplate;
    } else {
        // Handle 404 or display a default page
        include __DIR__ . '/../private/views/pages/404.php';
    }

    // Include the common footer
    include __DIR__ . '/../private/views/templates/footer.php';


    if ($site['showPopup'] && !isset($_SESSION['popupShown'])) {
        // Include your popup HTML or JavaScript code here
        include __DIR__ . '/../private/views/Popups/popup.php';

        // Set a session variable to remember that the popup has been shown
        $_SESSION['popupShown'] = true;
    }
}



