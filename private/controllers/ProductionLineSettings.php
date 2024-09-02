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

    public static function updateProductionLineSettings(int $productionLineId, int $userId, bool $autoImportExport, bool $autoPowerMachine, bool $autoSave)
    {
        Database::update("productionLineSettings", ['auto_import_export', 'auto_power_machine', 'auto_save'], [$autoImportExport ? 1 : 0, $autoPowerMachine ? 1 : 0, $autoSave ? 1 : 0], ['production_line_id' => $productionLineId, 'user_id' => $userId]);
        return Database::get("productionLineSettings", ['*'], [], ['production_line_id' => $productionLineId, 'user_id' => $userId]);
    }

    public static function deleteProductionLineSettings(int $productionLineId)
    {
        return Database::delete("productionLineSettings", ['production_line_id' => $productionLineId]);
    }

    public static function deleteProductionLineSettingsByUser(int $userId)
    {
        return Database::delete("productionLineSettings", ['user_id' => $userId]);
    }


}