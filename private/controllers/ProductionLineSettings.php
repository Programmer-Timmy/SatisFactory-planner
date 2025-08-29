<?php

class ProductionLineSettings
{

    public static function getProductionLineSettings(int $productionLineId)
    {
        return Database::get("productionlinesettings", ['*'], [], ['production_line_id' => $productionLineId]);
    }

    public static function addProductionLineSettings(int $productionLineId)
    {
        Database::insert("productionlinesettings", ['production_line_id'], [$productionLineId]);
        return Database::get("productionlinesettings", ['*'], [], ['production_line_id' => $productionLineId]);
    }

    public static function updateProductionLineSettings(int $productionLineId, bool $autoImportExport, bool $autoPowerMachine, bool $autoSave)
    {
        Database::update("productionlinesettings", ['auto_import_export', 'auto_power_machine', 'auto_save'], [$autoImportExport ? 1 : 0, $autoPowerMachine ? 1 : 0, $autoSave ? 1 : 0], ['production_line_id' => $productionLineId]);
        return Database::get("productionlinesettings", ['*'], [], ['production_line_id' => $productionLineId]);
    }

    public static function deleteProductionLineSettings(int $productionLineId, NewDatabase | null $database = null)
    {
        $database = $database ?? new NewDatabase();
        return $database->delete("productionlinesettings", ['production_line_id' => $productionLineId]);
    }

}