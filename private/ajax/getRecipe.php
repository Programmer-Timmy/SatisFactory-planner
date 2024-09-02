<?php

$id = $_GET['id'];

$recipe = Recipes::getRecipeById($id);

if (empty($recipe)) {
    echo json_encode(['error' => 'Invalid request']);
    exit();
}


echo json_encode($recipe);