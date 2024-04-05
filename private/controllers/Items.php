<?php

class Items
{

    public static function getAllItems()
    {
        return Database::getAll("items" , ['*'], [], [], 'name ASC');
    }

}