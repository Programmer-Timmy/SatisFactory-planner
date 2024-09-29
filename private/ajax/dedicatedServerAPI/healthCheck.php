<?php

if (!isset($_POST)) {
    die(json_encode(['status' => 'error', 'message' => 'Invalid request']));
}

if (!isset($_POST['saveGameId'])) {
    die(json_encode(['status' => 'error', 'message' => 'Invalid request']));
}

$saveGameId = $_POST['saveGameId'];
$serverToken = GameSaves::getServerToken($saveGameId);

try {
    $client = new APIClient('192.168.2.11', 7777, $serverToken);
    $response = $client->post('HealthCheck', ['ClientCustomData' => '']);

    // Assuming 'HealthCheck' has some specific response format
    $output = '';
    foreach ($response['data'] as $key => $value) {
        $output .= "<div class='row'><div class='col-6'>$key</div><div class='col-6'>$value</div></div>";
    }

    echo json_encode(['status' => 'success', 'data' => $output]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
