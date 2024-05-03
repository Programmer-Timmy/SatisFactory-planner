<?php

class ProductionLines
{
    public static function addProductionline($gameSaveId, $title)
    {
        return Database::insert("production_lines", ['game_saves_id', 'title'], [$gameSaveId, $title]);
    }

    public static function getProductionLinesByGameSave(int $gameSaveId)
    {
        return Database::getAll("production_lines", ['production_lines.id as id', 'production_lines.title as name', 'power_consumbtion as `power_consumbtion`', 'production_lines.updated_at', 'active'], ['game_saves' => 'game_saves.id = production_lines.game_saves_id'], ['production_lines.game_saves_id' => $gameSaveId], 'production_lines.updated_at DESC');
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

    public static function getProductionByProductionLine(int $productionLineId)
    {
        return Database::getAll("production", ['recipes.id as recipe_id', 'production.local_usage', 'recipes.name as recipe_name', 'production.export_ammount_per_min as export_amount_per_min', 'buildings.name as building_name', 'buildings.power_used', 'production.quantity as product_quantity'], ["recipes" => "recipes.id = production.recipe_id", 'buildings' => 'buildings.id = recipes.buildings_id'], ["production_lines_id" => $productionLineId]);
    }

    public static function getPowerByProductionLine(int $productionLineId)
    {
        return Database::getAll("power", ['power.*, buildings.name as building', 'buildings.power_used'], ["buildings" => "buildings.id = power.buildings_id"], ["production_lines_id" => $productionLineId]);
    }

    public static function saveProductionLine(array $imports, array $production, array $power, string $totalConsumption, int $id)
    {
        Database::delete("input", ['production_lines_id' => $id]);
        Database::delete("production", ['production_lines_id' => $id]);
        Database::delete("power", ['production_lines_id' => $id]);
        Database::delete("output", ['production_lines_id' => $id]);

        Database::update("production_lines",['power_consumbtion', 'updated_at'], [$totalConsumption, date('Y-m-d H:i:s')], ['id' => $id]);
        foreach ($imports as $import) {
            Database::insert("input", ['production_lines_id', 'items_id', 'ammount'], [$id, $import->id, $import->ammount]);
        }
        foreach ($production as $prod) {
            $recipes = Recipes::getRecipeById($prod->recipe_id);
            Database::insert("production", ['production_lines_id', 'recipe_id', 'quantity', 'local_usage', 'export_ammount_per_min'], [$id, $prod->recipe_id, $prod->product_quantity, $prod->usage, $prod->export_amount_per_min]);
            Database::insert("output", ['production_lines_id', 'items_id', 'ammount'], [$id, $recipes->item_id, $prod->export_amount_per_min]);
        }
        foreach ($power as $pow) {
            Database::insert("power", ['production_lines_id', 'buildings_id', 'building_ammount', 'clock_speed', 'power_used', 'user'], [$id, $pow->buildings_id, $pow->building_ammount, $pow->clock_speed, $pow->power_used, $pow->user]);
        }

        return true;
    }

    public static function deleteProductionLine(int $id)
    {
        Database::delete("input", ['production_lines_id' => $id]);
        Database::delete("production", ['production_lines_id' => $id]);
        Database::delete("power", ['production_lines_id' => $id]);
        Database::delete("output", ['production_lines_id' => $id]);
        Database::delete("production_lines", ['id' => $id]);
        return true;
    }

    public static function deleteProductionLineOnGameId(int $id)
    {
        $producitonId = Database::getAll("production_lines", ['id'], [], ['game_saves_id' => $id]);
        foreach ($producitonId as $prodId) {
            Database::delete("input", ['production_lines_id' => $prodId->id]);
            Database::delete("production", ['production_lines_id' => $prodId->id]);
            Database::delete("power", ['production_lines_id' => $prodId->id]);
            Database::delete("output", ['production_lines_id' => $prodId->id]);
            Database::delete("production_lines", ['id' => $prodId->id]);
        }
    }

    public static function updateProductionLine(int $productLineId, string $productionLineName, int $active)
    {
        self::changeActiveStats($productLineId, $active);
        Database::update("production_lines", ['title'], [$productionLineName], ['id' => $productLineId]);
        return true;
    }

    public static function changeActiveStats(int $productLineId, int $active)
    {
        $updated_at = Database::get("production_lines", ['updated_at'],[], ['id' => $productLineId]);
        Database::update("production_lines", ['active', 'updated_at'], [$active, $updated_at->updated_at], ['id' => $productLineId]);
    }


}