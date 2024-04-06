<?php

class Buildings
{
    public static function getAllBuildings()
    {
        return Database::getAll("buildings", ['*'], [], [], 'name ASC');

    }
}