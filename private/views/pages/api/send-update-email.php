<?php
// send update mail to users
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {

    $headers = getallheaders();

    $validation = AuthControler::validateAdminToken($headers);

    if ($validation['success'] === false) {
        http_response_code(403);
        echo json_encode($validation);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    try {
        $users = Users::getUpdateEmailUsers();
        foreach ($users as $user) {
            $success = Mailer::sendWebsiteUpdateEmail($user);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => ['code' => 500, 'message' => 'Internal Server Error']]);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Emails sent successfully', 'users' => count($users)]);
}