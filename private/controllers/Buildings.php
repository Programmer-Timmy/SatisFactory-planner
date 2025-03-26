<?php

class Buildings
{
    public static function getAllBuildings()
    {
        return Database::getAll("buildings", ['*'], [], ['power_generation' => 0], 'name ASC');

    }

    public static function getBuildingById(int $id)
    {
        return Database::get("buildings", ['*'], [], ['id' => $id]);
    }

    public static function getPowerBuildings()
    {
        return Database::query("SELECT * FROM buildings WHERE power_generation > 0 ORDER BY name ASC");
    }

    public static function getBuildingIdByName(string $name)
    {
        return Database::get("buildings", ['id'], [], ['name' => $name]);
    }

    public static function getPowerAndPowerUsedByBuildings()
    {
        return Database::getAll("buildings");
    }
}