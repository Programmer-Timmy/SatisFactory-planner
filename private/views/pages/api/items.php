<?php

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $requestData = $_GET;
    $items = null;
    // Extracting data from JSON request
    if (empty($requestData['id'])) {
        $items = Items::getAllItemsPRecipe();
        $response = array(
            'success' => true,
            'count' => count($items),
            'items' => $items
        );
        echo json_encode($response);
        exit();
    }
    if (intval($requestData['id']) == $requestData['id']) {
        $id = $requestData['id'];
        $items = Items::getItemById($id);
    }

    if (empty($items)) {
        $response = array(
            'success' => false,
            'error' => [
                "code" => "BUILDING_NOT_FOUND",
                "message" => "The requested building was not found"
            ]
        );
        http_response_code(404);
        echo json_encode($response);
        exit();
    }

    $response = array(
        'success' => true,
        'item' => $items
    );

    echo json_encode($response);
} else {
    // Handle invalid request method
    http_response_code(405); // Method Not Allowed
    echo json_encode(
        [
            'success' => false,
            'error' => [
                "code" => "INVALID_REQUEST_METHOD",
                "message" => "Invalid request method"
            ]
        ]
    );
}