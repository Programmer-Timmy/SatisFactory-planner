<?php
header('Content-Type: application/json');

// Controleer de methode
$method = $_SERVER['REQUEST_METHOD'];

$headers = getallheaders();

if ($method === 'GET') {
//    validate token
    $validation = AuthControler::validateAdminToken($headers);

    if ($validation['success'] === false) {
        http_response_code(403);
        echo json_encode($validation);
        exit;
    }

    $mainteneceMode = SiteSettings::getMaintenanceMode();
    echo json_encode(['success' => true, 'maintenance_mode' => $mainteneceMode === 1]);
    exit;
}

if ($method === 'POST') {
    // Controleer de Authorization-header
    $authHeader = $headers['Authorization'] ?? '';

    $validation = AuthControler::validateAdminToken($headers);

    if ($validation['success'] === false) {
        http_response_code(403);
        echo json_encode($validation);
        exit;
    }

    // Lees JSON-body
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['enabled']) || !is_bool($input['enabled'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => ['code' => 400, 'message' => 'Bad Request. Give a boolean value for "enabled"']]);
        exit;
    }

    // Update de database
    SiteSettings::setMaintenanceMode($input['enabled']);

    echo json_encode(['success' => true, 'message' => 'Onderhoudsmodus is nu ' . ($input['enabled'] ? 'ingeschakeld' : 'uitgeschakeld')]);
    exit;
}

// Ongeldige methode
http_response_code(405);
echo json_encode(['message' => 'Methode niet toegestaan']);
