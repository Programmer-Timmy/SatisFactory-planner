<?php

if (!isset($_GET['id'])) {
    header('Location: /');
    exit();
}

$resourses = Recipes::getRecipeResources($_GET['id']);

if (empty($resourses)) {
    header('Location: /');
    exit();
}else{
    echo json_encode($resourses);
}
