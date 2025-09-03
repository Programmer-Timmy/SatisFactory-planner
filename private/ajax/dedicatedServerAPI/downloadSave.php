<?php
//                                    gameSaveId:
//                                    sessionName:
//                                    saveName:
if (!isset($_POST['gameSaveId']) || !isset($_POST['sessionName']) || !isset($_POST['saveName'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$hasPermission = GameSaves::checkAccess($_POST['gameSaveId'], $_SESSION['userId'], Permission::SERVER_MANAGE);

if (!$hasPermission) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$gameSaveId = $_POST['gameSaveId'];
$sessionName = $_POST['sessionName'];
$saveName = $_POST['saveName'];

$dedicatedServer = DedicatedServer::getBySaveGameId($gameSaveId);

if (!$dedicatedServer) {
    http_response_code(404);
    echo json_encode(['error' => 'Dedicated server not found']);
    exit;
}

$client = new APIClient($dedicatedServer->server_ip, $dedicatedServer->server_port, $dedicatedServer->server_token);

try {
    $response = $client->post('DownloadSaveGame', [
        'SaveName' => $saveName
    ]);

    if ($response['response_code'] !== 200) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to download save']);
        exit;
    }

    $output = $response['file_content'];
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($saveName) . '"');
    echo base64_decode($output);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

