<?php
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
$url = $_POST['url'] ?? null;
$type = $_POST['type'] ?? null;

if (!$ip && !$url && !$type) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields you must provide at least one of the following: ip, url, type']);
    exit();
}

if ($type !== '404' && $type !== '403') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid type provided must be one of the following: all, 404, 403']);
    exit();
}

$errorLogs = ErrorHandeler::searchErrorLogs($ip, $url, $type);
foreach ($errorLogs as $errorLog) {
    $errorLog->error_timestamp = GlobalUtility::dateTimeToLocal($errorLog->error_timestamp);
}

header('Content-Type: text/html');
http_response_code(200);
if (!$errorLogs) {
    ?>
    <div class="alert alert-info">No error logs found</div>
    <?php
    exit();
}
echo GlobalUtility::createTable($errorLogs, ['username', 'requested_url', 'ip_address', 'referrer_url', 'error_timestamp'], customId: $type . "ErrorLogsTable");

