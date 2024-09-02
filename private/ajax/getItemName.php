<?php

$id = $_GET['id'];

if (!isset($id)) {
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

$item = Items::getItemById($id);

if (empty($item)) {
    exit();
}

echo $item->name;


