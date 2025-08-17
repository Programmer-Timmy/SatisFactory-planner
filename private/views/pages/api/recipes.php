<?php
header('Content-Type: application/json');

// get and web request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $requestData = $_GET;
    $recipe = null;
    // Extracting data from JSON request
    if (empty($requestData['id'])) {
        $recipes = Recipes::getAllRecipes();
        $response = array(
            'success' => true,
            'count' => count($recipes),
            'recipes' => $recipes
        );
        echo json_encode($response);
        exit();
    }
    if (intval($requestData['id']) == $requestData['id']) {
        $id = $requestData['id'];
        $recipe = Recipes::getRecipeWithDetails($id);
    }

    if (empty($recipe)) {
        $response = array(
            'success' => false,
            'error' => [
                "code" => "RECIPE_NOT_FOUND",
                "message" => "The requested recipe was not found"
            ]
        );
        http_response_code(404);
        echo json_encode($response);
        exit();
    }

    $response = array(
        'success' => true,
        'recipe' => $recipe
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