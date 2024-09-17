<?php

class PowerProduction
{
    /**
     * @param int $gameSaveId
     * @return int
     */
    public static function getPowerProduction(int $gameSaveId): array
    {
        $powerProduction = Database::getAll("power_production", ['power_production.id', 'building_id', 'power_generation', 'buildings.class_name','buildings.name as building_name', 'clock_speed', 'amount'], ['buildings' => 'buildings.id = building_id'], ['game_saves_id' => $gameSaveId]);
        return $powerProduction;
    }

    /**
     * @param int $gameSaveId
     * @param int $buildingId
     * @param int $amount
     * @param int $clockSpeed
     * @return void
     */
    public static function addPowerProduction(int $gameSaveId, int $buildingId, int $amount, int $clockSpeed): int
    {
        $database = new Database();
        Database::insert('power_production', ['building_id', 'amount', 'clock_speed', 'game_saves_id'], [$buildingId, $amount, $clockSpeed, $gameSaveId], $database);
        return $database->connection->lastInsertId();
    }

    /**
     * @param int $powerProductionId
     * @return void
     */
    public static function deletePowerProduction(int $powerProductionId): void
    {
        Database::delete('power_production', ['id' => $powerProductionId]);
    }

    /**
     * @param int $powerProductionId
     * @param int $amount
     * @param int $clockSpeed
     * @return void
     */
    public static function updatePowerProduction(int $powerProductionId, int $amount, int $clockSpeed): void
    {
        Database::update('power_production', ['amount' => $amount, 'clock_speed' => $clockSpeed], ['id' => $powerProductionId], []);
    }

}