<?php
// get and web request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $requestData = $_GET;
    // Extracting data from JSON request
    if (empty($requestData['id'])) {
        $response = array(
            'success' => true,
            'recipes' => Recipes::getAllRecipeWithResources()
        );
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    $id = $requestData['id'];
    $recipe = Recipes::getRecipeWithResources($id);

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