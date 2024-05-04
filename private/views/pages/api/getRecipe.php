<?php
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $requestData = $_GET;
    // Extracting data from JSON request
    $id = $requestData['id'];
    if (empty($id)) {
        $response = array(
            'success' => false,
            'error' => 'ID is required'
        );
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    $recipe = Recipes::getRecipeById($id);

    if (empty($recipe)) {
        $response = array(
            'success' => false,
            'error' => 'Recipe not found'
        );
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    $response = array(
        'success' => true,
        'recipe' => $recipe
    );

    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    // Handle invalid request method
    http_response_code(405); // Method Not Allowed
    echo json_encode(array('error' => 'Invalid request method'));
}