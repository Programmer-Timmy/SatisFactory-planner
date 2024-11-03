<?php

if (!isset($_POST)) {
    die(json_encode(['status' => 'error', 'message' => 'Invalid request']));
}

if (!isset($_POST['saveGameId'])) {
    die(json_encode(['status' => 'error', 'message' => 'Invalid request']));
}

function secondsToHMS($seconds)
{
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;

    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}

$saveGameId = $_POST['saveGameId'];

if (!GameSaves::checkAccessUser($saveGameId)) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'You do not have access to this save game']));
}

$dedicatedServer = DedicatedServer::getBySaveGameId($saveGameId);

if (!$dedicatedServer) {
    die(json_encode(['status' => 'error', 'message' => 'Dedicated server not found']));
}
// Usage
try {
    $client = new APIClient($dedicatedServer->server_ip, $dedicatedServer->server_port, $dedicatedServer->server_token);
    $response = $client->post('QueryServerState');

    if ($response['response_code'] !== 200) {
        die(json_encode(['status' => 'error', 'message' => 'Failed to query server state']));
    }


    $serverState = $response['data'];

    $serverState['serverGameState']['totalGameDuration'] = secondsToHMS($serverState['serverGameState']['totalGameDuration']);

    echo json_encode(['status' => 'success', 'data' => $serverState]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}