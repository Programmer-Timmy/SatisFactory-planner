<?php
header('Content-Type: application/json');
if (!isset($_POST['gameSaveId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$hasPermission = GameSaves::checkAccess($_POST['gameSaveId'], $_SESSION['userId'], Permission::SERVER_MANAGE);

if (!$hasPermission) {
    Response::error('Access denied', 403);
    exit;
}

$gameSaveId = $_POST['gameSaveId'];

$dedicatedServer = DedicatedServer::getBySaveGameId($gameSaveId);

if (!$dedicatedServer) {
    Response::error('No dedicated server has been setup', 404);
    exit;
}

$client = new APIClient($dedicatedServer->server_ip, $dedicatedServer->server_port, $dedicatedServer->server_token);

//try {
//    $response = $client->post('shutdonwServer');
//
//    if ($response['response_code'] !== 200) {
//        Response::error($response['data'], $response['response_code']);
//        exit;
//    }
//
//    $output = $response['data'];
//    Response::success($output);
//    exit;
//} catch (Exception $e) {
//    http_response_code(500);
//    Response::error($e->getMessage());
//}

