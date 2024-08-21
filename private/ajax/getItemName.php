<?php

$id = $_GET['id'];

if (!isset($id)) {
    header('Location: /');
    exit();
}

$item = Items::getItemById($id);

if (empty($item)) {
    exit();
}

echo $item->name;


