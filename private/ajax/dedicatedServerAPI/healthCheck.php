<?php

if (!isset($_POST)) {
    die(json_encode(['status' => 'error', 'message' => 'Invalid request']));
}

if (!isset($_POST['saveGameId'])) {
    die(json_encode(['status' => 'error', 'message' => 'Invalid request']));
}

$saveGameId = $_POST['saveGameId'];
$dedicatedServer = DedicatedServer::getBySaveGameId($saveGameId);

if (!$dedicatedServer) {
    die(json_encode(['status' => 'error', 'message' => 'Dedicated server not found']));
}
try {
    $client = new APIClient($dedicatedServer->server_ip, $dedicatedServer->server_port, $dedicatedServer->server_token);
    $response = $client->post('HealthCheck', ['ClientCustomData' => '']);

    if ($response['response_code'] !== 200) {
        die(json_encode(['status' => 'error', 'message' => 'Failed to query server state']));
    }

    $output = $response['data'];

    echo json_encode(['status' => 'success', 'data' => $output]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}