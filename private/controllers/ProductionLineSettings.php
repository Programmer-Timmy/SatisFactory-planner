<?php

class ProductionLineSettings
{

    public static function getProductionLineSettings(int $productionLineId, int $userId)
    {
        return Database::get("productionLineSettings", ['*'], [], ['production_line_id' => $productionLineId, 'user_id' => $userId]);
    }

    public static function addProductionLineSettings(int $productionLineId, int $userId)
    {
        Database::insert("productionLineSettings", ['production_line_id', 'user_id'], [$productionLineId, $userId]);
        return Database::get("productionLineSettings", ['*'], [], ['production_line_id' => $productionLineId, 'user_id' => $userId]);
    }


}