<?php

$id = $_GET['id'];

$recipe = Recipes::getRecipeByIdAjax($id);


if (empty($recipe)) {
    echo json_encode(['error' => 'Invalid request']);
    exit();
}
$resources = Recipes::getRecipeResources($id);
$building = Buildings::getBuildingById($recipe->buildings_id);

$recipe->resources = $resources;
$recipe->building = $building;

unset($recipe->buildings_id);

echo json_encode($recipe);