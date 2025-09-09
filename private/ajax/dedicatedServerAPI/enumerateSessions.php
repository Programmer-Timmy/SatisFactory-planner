<?php

header('Content-Type: application/json');
if (!isset($_POST)) {
    die(json_encode(['status' => 'error', 'message' => 'Invalid request']));
}

if (!isset($_POST['saveGameId'])) {
    die(json_encode(['status' => 'error', 'message' => 'Invalid request']));
}

$saveGameId = $_POST['saveGameId'];
$userId = $_SESSION['userId'];

if (!GameSaves::checkAccess($saveGameId, $userId, Permission::SERVER_MANAGE)) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'You do not have access to this save game']));
}

$dedicatedServer = DedicatedServer::getBySaveGameId($saveGameId);

if (!$dedicatedServer) {
    die(json_encode(['status' => 'error', 'message' => 'Dedicated server not found']));
}

