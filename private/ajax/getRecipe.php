<?php

$id = $_GET['id'];

$recipe = Recipes::getRecipeById($id);

if (empty($recipe)) {
    header('Location: /');
    exit();
}

echo json_encode($recipe);