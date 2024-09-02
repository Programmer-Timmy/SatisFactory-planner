<?php

if (!isset($_GET['id'])) {
    header('Location: /');
    exit();
}

$resourses = Recipes::getRecipeResources($_GET['id']);

if (empty($resourses)) {
    echo json_encode(['error' => 'Invalid request']);
    exit();
}else{
    echo json_encode($resourses);
}
