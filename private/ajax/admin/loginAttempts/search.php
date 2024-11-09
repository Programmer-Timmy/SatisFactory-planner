<?php
header('Content-Type: application/json'); // set the content type to json for error responses

if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'You are not authorized to access this page']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method']);
    exit();
}
$ip = $_POST['ip'] ?? null;
$year = $_POST['year'] ?? null;
$user = $_POST['user'] ?? null;
$type = $_POST['type'] ?? null;


if (!$ip && !$year && !$user && !$type) {
//    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields you must provide at least one of the following: ip, year, user, type']);
    exit();
}

$loginAttempts = AuthControler::searchLoginAttempts($ip, $user, $year, $type);

http_response_code(200);
header('Content-Type: text/html');

if (!$loginAttempts) {
    ?>
    <div class="alert alert-info">No login attempts found</div>
    <?php
    exit();
}
echo GlobalUtility::createTable($loginAttempts, ['username', 'ip_address', 'success', 'login_timestamp'], enableBool: false, customId: 'loginAttemptsTable');