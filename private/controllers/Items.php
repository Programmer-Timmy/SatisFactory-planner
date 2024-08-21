<?php

class Items
{

    public static function getAllItems()
    {
        return Database::getAll("items" , ['*'], [], [], 'name ASC');
    }

    public static function getItemById($id)
    {
        return Database::get("items", ['*'],[], ['id' => $id]);
    }

}