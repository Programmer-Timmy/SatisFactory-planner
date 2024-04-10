<?php

class Buildings
{
    public static function getAllBuildings()
    {
        return Database::getAll("buildings", ['*'], [], [], 'name ASC');

    }

    public static function getBuildingById(int $id)
    {
        return Database::get("buildings", ['*'], [], ['id' => $id]);
    }
}