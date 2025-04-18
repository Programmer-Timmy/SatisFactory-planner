<?php
ob_start();

$continue = true;

// Include necessary files
require_once __DIR__ . '/../private/autoload.php';
require_once __DIR__ . '/../private/config/settings.php';

// Start a session
session_start();
// Global variables
global $site;
global $allowedIPs;

// check if ip is blocked
if (AuthControler::isIPBlocked($_SERVER['REMOTE_ADDR'])) {
    include __DIR__ . '/../private/views/errors/block.php';
    exit();
}


// handle maintenance mode
if ($site['maintenance'] && !in_array($_SERVER['REMOTE_ADDR'], $allowedIPs) && !in_array($_SERVER['REQUEST_URI'], $site['excludeMaintenancePages'])) {
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
// get the X-CSRF-Token from the headers and check if it is the same as the session token
if ($site['ajax'] && (
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
        (isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true')
    )) {
    // Check for CSRF token in session and headers
    if (empty($_SESSION['csrf_token']) || empty($_SERVER['HTTP_X_CSRF_TOKEN']) || $_SESSION['csrf_token'] !== $_SERVER['HTTP_X_CSRF_TOKEN']) {
        ErrorHandeler::add403Log($requestedPage, $_SERVER['HTTP_REFERER'] ?? null, $_SESSION['userId'] ?? null);
        sendJsonResponse(403, 'Invalid or missing CSRF token');
    }

    // Check if the requested AJAX file exists
    $ajaxFile = __DIR__ . "/../private/ajax$require.php";
    if (file_exists($ajaxFile)) {
        include $ajaxFile;
        exit();
    } else {
        ErrorHandeler::add404Log($requestedPage, $_SERVER['HTTP_REFERER'] ?? null, $_SESSION['userId'] ?? null);
        sendJsonResponse(404, 'Invalid Ajax endpoint');
    }
}


// if url has api in it load the api file and exit
if (str_contains($require, '/api')) {
    if (file_exists(__DIR__ . "/../private/views/pages$require.php")) {
        include __DIR__ . "/../private/views/pages$require.php";
        exit();
    }
    ErrorHandeler::add404Log($requestedPage, $_SERVER['HTTP_REFERER'] ?? null, $_SESSION['userId'] ?? null);
    ErrorHandeler::blockIPForRapid404Errors($_SERVER['REMOTE_ADDR']);
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => ['code' => '404', 'message' => 'Invalid API endpoint', 'timestamp' => date('Y-m-d\TH:i:s\Z', time())
        , 'requestedEndpoint' => $require], 'endpoints' => ['/api/buildings', '/api/items', '/api/recipes']]);
    exit();
}

if ($site['admin']['enabled']) {
    $admin = $site['admin'];
    $pageTemplate = __DIR__ . "/../private/Views/pages$require.php";
    $pageDirectory = __DIR__ . "/../private/Views/pages$require";
    if (file_exists($pageTemplate) || is_dir($pageDirectory)) {
        if (str_contains($require, $admin['filterInUrl']) && $require !== $site['redirect'] && !in_array($require, $admin['skipChecks'])) {
            if (!isset($_SESSION[$admin['sessionName']])) {
//                if already logged in show the 403 page
                if (isset($_SESSION[$site['accounts']['sessionName']])) {
                    ErrorHandeler::add403Log($requestedPage, $_SERVER['HTTP_REFERER'] ?? null, $_SESSION[$site['accounts']['sessionName']]);
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
    $pageDirectory = __DIR__ . "/../private/Views/pages$require";

    if (file_exists($pageTemplate) || is_dir($pageDirectory)) {
        if (str_contains($require, $accounts['filterInUrl']) && !str_contains($require, $site['redirect']) && !in_array(substr($require, 1), $accounts['skipChecks'])) {
            if (!isset($_SESSION[$accounts['sessionName']])) {
                if ($site['saveUrl']) {
                    $_SESSION['redirect'] = $requestedPage;
                }

                $continue = false;
                include __DIR__ . '/../private/views/templates/header.php';
                include __DIR__ . '/../private/views/templates/navbar.php';
                require_once __DIR__ . '/../private/views/pages/login/index.php';
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

    // check if its an directory
    if (is_dir(__DIR__ . "/../private/views/pages$requestedPage")) {
        $requestedPage = $requestedPage . '/index';
    }

    // Include the specific page content
    $pageTemplate = __DIR__ . "/../private/views/pages/$requestedPage.php";

    if (file_exists($pageTemplate)) {
        include $pageTemplate;
    } else {
        // Handle 404 or display a default page
        ErrorHandeler::blockIPForRapid404Errors($_SERVER['REMOTE_ADDR']);
        ErrorHandeler::add404Log($requestedPage, $_SERVER['HTTP_REFERER'] ?? null, $_SESSION['userId'] ?? null);
        include __DIR__ . '/../private/views/errors/404.php';
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


// functions
/**
 * Send a JSON response with a given HTTP status code and message.
 *
 * @param int $statusCode
 * @param string $message
 */
function sendJsonResponse(int $statusCode, string $message): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit();
}
