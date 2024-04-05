<?php

class ProductionLines
{
    public static function getProductionLinesByGameSave(int $gameSaveId)
    {
        return Database::getAll("production_lines", ['production_lines.id as id', 'production_lines.title as name', 'power_consumbtion as `power_consumbtion`', 'production_lines.updated_at'], ['game_saves' => 'game_saves.id = production_lines.game_saves_id'], ['production_lines.game_saves_id' => $gameSaveId]);
    }

    public static function checkProductionLineVisability(int $gameSaveId, int $userId)
    {
        $check = Database::get("users_has_game_saves", ['*'], [], ['game_saves_id' => $gameSaveId, 'users_id' => $userId]);
        if ($check) {
            return true;
        }
        return false;
    }

    public static function getProductionLineById(int $id)
    {
        return Database::get("production_lines", ['*'], [], ['id' => $id]);
    }

    public static function getImportsByProductionLine(int $productionLineId)
    {
        return Database::getAll("input", ['ammount', 'name', 'items_id'], ["items" => "items.id = input.items_id"], ["production_lines_id" => $productionLineId]);
    }



}