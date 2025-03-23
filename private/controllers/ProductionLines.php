<?php

class ProductionLines
{
    public static function addProductionline($gameSaveId, $title)
    {
        $id = Database::insert("production_lines", ['game_saves_id', 'title'], [$gameSaveId, $title]);
        ProductionLineSettings::addProductionLineSettings($id);
        return $id;

    }

    public static function getProductionLinesByGameSave(int $gameSaveId)
    {
        $productionLines = Database::getAll("production_lines", ['production_lines.id as id', 'production_lines.title as name', 'power_consumbtion as `power_consumbtion`', 'production_lines.updated_at', 'active'], ['game_saves' => 'game_saves.id = production_lines.game_saves_id'], ['production_lines.game_saves_id' => $gameSaveId], 'production_lines.updated_at DESC');

        foreach ($productionLines as $productionLine) {
            $productionLine->checklist = Database::getAll("checklist", [
                'SUM(been_build) / COUNT(*) * 100 as been_build_percentage',
                'SUM(been_tested) / COUNT(*) * 100 as been_tested_percentage'
            ], [], ['production_lines_id' => $productionLine->id]);
        }

        return $productionLines;
    }

    public static function checkProductionLineVisability(int $gameSaveId, int $userId)
    {
        $check = Database::get("users_has_game_saves", ['*'], [], ['game_saves_id' => $gameSaveId, 'users_id' => $userId, 'accepted' => 1]);
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
        return Database::getAll("production", ['production.id' ,'items.name AS item_name_1', ' items2.name AS item_name_2', 'local_usage2', 'export_ammount_per_min2', 'recipes.id as recipe_id', 'production.local_usage', 'recipes.name as recipe_name', 'production.export_ammount_per_min as export_amount_per_min', 'buildings.name as building_name', 'buildings.power_used', 'production.quantity as product_quantity'], ["recipes" => "recipes.id = production.recipe_id", 'buildings' => 'buildings.id = recipes.buildings_id', 'items' => 'recipes.item_id = items.id left join items as items2 on recipes.item_id2 = items2.id'], ["production_lines_id" => $productionLineId]);
    }

    public static function getPowerByProductionLine(int $productionLineId)
    {
        return Database::getAll("power", ['power.*, buildings.name as building', 'buildings.power_used'], ["buildings" => "buildings.id = power.buildings_id"], ["production_lines_id" => $productionLineId]);
    }

    public static function saveProductionLine(array $imports, array $production, array $power, string $totalConsumption, int $id)
    {
        $database = new NewDatabase();
        $database->beginTransaction();
        try {
            $database->delete("input", ['production_lines_id' => $id]);
            $database->delete("power", ['production_lines_id' => $id]);
            $database->delete("output", ['production_lines_id' => $id]);

            $database->update("production_lines",['power_consumbtion', 'updated_at'], [$totalConsumption, date('Y-m-d H:i:s')], ['id' => $id]);
            foreach ($imports as $import) {
                $database->insert("input", ['production_lines_id', 'items_id', 'ammount'], [$id, $import->id, $import->ammount]);
            }

            $updatedAndNewProduction = [];
            $newAndOldIds = [];
            $allProduction = Database::getAll("production", ['id'], [], ['production_lines_id' => $id]);
            foreach ($production as $prod) {
                $recipes = Recipes::getRecipeById($prod->recipe_id);

                $existingProduction = $database->get("production", ['id'], [], ['id' => $prod->id]);

                if ($existingProduction) {
                    $database->update(
                        "production",
                        [
                            'recipe_id',
                            'quantity',
                            'local_usage',
                            'export_ammount_per_min',
                            'export_ammount_per_min2',
                            'local_usage2'
                        ],
                        [
                            $prod->recipe_id,
                            $prod->product_quantity,
                            $prod->usage,
                            $prod->export_amount_per_min,
                            $prod->export_ammount_per_min2,
                            $prod->local_usage2
                        ],
                        [
                            'id' => $prod->id
                        ]
                    );
                    $updatedAndNewProduction[] = $prod->id;

                } else {
                    $database->insert("production", ['production_lines_id', 'recipe_id', 'quantity', 'local_usage', 'export_ammount_per_min', 'export_ammount_per_min2', 'local_usage2'], [$id, $prod->recipe_id, $prod->product_quantity, $prod->usage, $prod->export_amount_per_min, $prod->export_ammount_per_min2, $prod->local_usage2]);
                    $updatedAndNewProduction[] = $database->connection->lastInsertId();
                    $newAndOldIds[] = [
                        'new' => (int) $database->connection->lastInsertId(),
                        'old' => $prod->id
                    ];
                }


                $database->delete("output", ['production_lines_id' => $id]);
                $database->insert("output", ['production_lines_id', 'items_id', 'ammount'], [$id, $recipes->item_id, $prod->export_amount_per_min]);

                if ($recipes->item_id2) {
                    $database->insert("output", ['production_lines_id', 'items_id', 'ammount'], [$id, $recipes->item_id2, $prod->export_ammount_per_min2]);
                }
            }

            $deleteProduction = array_diff(array_column($allProduction, 'id'), $updatedAndNewProduction);
            foreach ($deleteProduction as $delete) {
                $database->delete("production", ['id' => $delete]);
            }

            foreach ($power as $pow) {
                $database->insert("power", ['production_lines_id', 'buildings_id', 'building_ammount', 'clock_speed', 'power_used', 'user'], [$id, $pow->buildings_id, $pow->building_ammount, $pow->clock_speed, $pow->power_used, $pow->user ? 1 : 0]);
            }
            $database->commit();

            return $newAndOldIds;
        } catch (Exception $e) {
            $database->rollBack();
            return false;
        }
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
        Database::update("production_lines", ['title', 'updated_at'], [$productionLineName, date('Y-m-d H:i:s')], ['id' => $productLineId]);
        return true;
    }

    public static function changeActiveStats(int $productLineId, int $active)
    {
        $updated_at = Database::get("production_lines", ['updated_at'],[], ['id' => $productLineId]);
        Database::update("production_lines", ['active', 'updated_at'], [$active, $updated_at->updated_at], ['id' => $productLineId]);
    }


}
