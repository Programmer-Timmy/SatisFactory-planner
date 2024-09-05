<?php
var_dump($_SERVER['REQUEST_METHOD']);
var_dump($_POST);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $requestData = json_decode(file_get_contents('php://input'), true);

        // Extracting data from JSON request
        $id = $requestData['id'];
        $active = $requestData['active'];

        // Call the function to change the active status
        ProductionLines::changeActiveStats($id, $active);

        // Prepare success response
        $response = array(
            'success' => true,
            'message' => "Active status changed for ID: $id to $active",
        );

        // Set response headers
        header('Content-Type: application/json');

        // Return JSON response
        echo json_encode($response);
    } catch (Exception $e) {
        // Prepare error response
        $response = array(
            'success' => false,
            'error' => $e->getMessage()
        );

        // Set response headers
        header('Content-Type: application/json');

        // Return JSON error response
        echo json_encode($response);
    }
} else {
    // Handle invalid request method
    http_response_code(405); // Method Not Allowed
    echo json_encode(array('error' => 'Invalid request method'));
}