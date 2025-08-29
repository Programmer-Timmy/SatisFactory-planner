<?php

class ProductionLines {
    public static function addProductionline($gameSaveId, $title) {
        $id = Database::insert("production_lines", ['game_saves_id', 'title'], [$gameSaveId, $title]);
        ProductionLineSettings::addProductionLineSettings($id);
        return $id;

    }

    public static function getProductionLinesByGameSave(int $gameSaveId) {
        $productionLines = Database::getAll("production_lines", ['production_lines.id as id', 'production_lines.title as name', 'power_consumbtion as `power_consumbtion`', 'production_lines.updated_at', 'active'], ['game_saves' => 'game_saves.id = production_lines.game_saves_id'], ['production_lines.game_saves_id' => $gameSaveId], 'production_lines.updated_at DESC');

        foreach ($productionLines as $productionLine) {
            $productionLine->checklist = Database::getAll("checklist", [
                'SUM(been_build) / COUNT(*) * 100 as been_build_percentage',
                'SUM(been_tested) / COUNT(*) * 100 as been_tested_percentage'
            ],                                            [], ['production_lines_id' => $productionLine->id]);
        }

        return $productionLines;
    }

    public static function checkProductionLineVisability(int $gameSaveId, int $productionLineId, int $userId) {
        $check = Database::get("users_has_game_saves", ['*'], [], ['game_saves_id' => $gameSaveId, 'users_id' => $userId, 'accepted' => 1]);
        $inGameSave = Database::get("production_lines", ['*'], [], ['id' => $productionLineId, 'game_saves_id' => $gameSaveId]);
        if ($check && $inGameSave) {
            return true;
        }
        return false;
    }

    public static function getProductionLineById(int $id) {
        return Database::get("production_lines", ['*'], [], ['id' => $id]);
    }

    public static function getImportsByProductionLine(int $productionLineId) {
        return Database::getAll("input", ['ammount', 'name', 'items_id'], ["items" => "items.id = input.items_id"], ["production_lines_id" => $productionLineId]);
    }

    public static function getProductionByProductionLine(int $productionLineId) {
        return Database::getAll(
            "production",
            [
                'production.id',
                'items.name AS item_name_1',
                'items2.name AS item_name_2',
                'local_usage2',
                'export_ammount_per_min2',
                'recipes.id as recipe_id',
                'production.local_usage',
                'recipes.name as recipe_name',
                'production.export_ammount_per_min as export_amount_per_min',
                'buildings.name as building_name', 'buildings.power_used',
                'production.quantity as product_quantity',
                'ps.clock_speed as clock_speed',
                'ps.use_somersloop as use_somersloop',
            ],
            [
                "recipes" => "recipes.id = production.recipe_id",
                'buildings' => 'buildings.id = recipes.buildings_id',
                'items' => 'recipes.item_id = items.id left join items as items2 on recipes.item_id2 = items2.id
                left join production_settings as ps on ps.id = production.production_settings_id',
            ],
            [
                "production_lines_id" => $productionLineId
            ]
        );
    }

    public static function getPowerByProductionLine(int $productionLineId) {
        return Database::getAll("power", ['power.*, buildings.name as building', 'buildings.power_used'], ["buildings" => "buildings.id = power.buildings_id"], ["production_lines_id" => $productionLineId]);
    }

    public static function saveProductionLine(array $imports, array $production, array $power, string $totalConsumption, int $id) {
        $database = new NewDatabase();
        $database->beginTransaction();
        try {
            $database->delete("input", ['production_lines_id' => $id]);
            $database->delete("power", ['production_lines_id' => $id]);
            $database->delete("output", ['production_lines_id' => $id]);

            $database->update("production_lines", ['power_consumbtion', 'updated_at'], [$totalConsumption, date('Y-m-d H:i:s')], ['id' => $id]);
            foreach ($imports as $import) {
                $database->insert("input", ['production_lines_id', 'items_id', 'ammount'], [$id, $import->id, $import->ammount]);
            }

            $updatedAndNewProduction = [];
            $newAndOldIds = [];
            $allProduction = Database::getAll("production", ['id'], [], ['production_lines_id' => $id]);
            $database->delete("output", ['production_lines_id' => $id]);
            foreach ($production as $prod) {
                $recipes = Recipes::getRecipeById($prod->recipe_id);
                $existingProduction = $database->get("production", ['id', 'production_settings_id'], [], ['id' => $prod->id]);
                $production_settings_id = self::insertUpdateProductionSettings($existingProduction->production_settings_id ?? null, $prod->produciton_settings, $database);
                if ($existingProduction) {
                    // if prod id is uuid
                    if (!is_numeric($prod->id) && $existingProduction->id !== $prod->id) {
                        $newAndOldIds[] = [
                            'new' => (int)$existingProduction->id,
                            'old' => $prod->id
                        ];
                    }
                    $prod->id = $existingProduction->id;
                    $database->update(
                        "production",
                        [
                            'recipe_id',
                            'quantity',
                            'local_usage',
                            'export_ammount_per_min',
                            'export_ammount_per_min2',
                            'local_usage2',
                            'production_settings_id'
                        ],
                        [
                            $prod->recipe_id,
                            $prod->product_quantity,
                            $prod->usage,
                            $prod->export_amount_per_min,
                            $prod->export_ammount_per_min2,
                            $prod->local_usage2,
                            $production_settings_id

                        ],
                        [
                            'id' => $prod->id
                        ]
                    );
                    $updatedAndNewProduction[] = $prod->id;
                } else {
                    $production_settings_id = self::insertUpdateProductionSettings(null, $prod->produciton_settings, $database);
                    $database->insert("production", ['production_lines_id', 'recipe_id', 'quantity', 'local_usage', 'export_ammount_per_min', 'export_ammount_per_min2', 'local_usage2', 'production_settings_id'], [$id, $prod->recipe_id, $prod->product_quantity, $prod->usage, $prod->export_amount_per_min, $prod->export_ammount_per_min2, $prod->local_usage2, $production_settings_id]);
                    $updatedAndNewProduction[] = $database->connection->lastInsertId();
                    $newAndOldIds[] = [
                        'new' => (int)$database->connection->lastInsertId(),
                        'old' => $prod->id
                    ];
                }


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
            error_log('Failed to save production line ID: ' . $id . ' - ' . $e->getMessage());
            return false;
        }
    }

    public static function deleteProductionLine(int $id) {
        Database::delete("input", ['production_lines_id' => $id]);
        Database::delete("production", ['production_lines_id' => $id]);
        Database::delete("power", ['production_lines_id' => $id]);
        Database::delete("output", ['production_lines_id' => $id]);
        Database::delete("production_lines", ['id' => $id]);
        return true;
    }

    public static function deleteProductionLineOnGameId(int $id, NewDatabase|null $database = null) {
        $database = $database ?? new NewDatabase();
        $producitonId = $database->getAll("production_lines", ['id'], [], ['game_saves_id' => $id]);
        foreach ($producitonId as $prodId) {
            $database->delete("input", ['production_lines_id' => $prodId->id]);
            $database->delete("production", ['production_lines_id' => $prodId->id]);
            $database->delete("power", ['production_lines_id' => $prodId->id]);
            $database->delete("output", ['production_lines_id' => $prodId->id]);
            $database->delete("production_lines", ['id' => $prodId->id]);
        }
    }

    public static function updateProductionLine(int $productLineId, string $productionLineName, int $active): void {
        $productLine = self::getProductionLineById($productLineId);
        if (!$productLine) {
            $_SESSION['error'] = 'Failed to update production line. Please try again later';
            return;
        }

        if (self::validateAccess($productLine->game_saves_id, $productLineId, $_SESSION['userId']) === false) {
            $_SESSION['error'] = 'You do not have permission to update this production line';
            return;
        }

        self::changeActiveStats($productLineId, $active);
        Database::update("production_lines", ['title', 'updated_at'], [$productionLineName, date('Y-m-d H:i:s')], ['id' => $productLineId]);

        $_SESSION['success'] = 'Production line updated successfully';
    }

    public static function changeActiveStats(int $productLineId, int $active) {
        $updated_at = Database::get("production_lines", ['updated_at'], [], ['id' => $productLineId]);
        Database::update("production_lines", ['active', 'updated_at'], [$active, $updated_at->updated_at], ['id' => $productLineId]);
    }

    private static function insertUpdateProductionSettings(int|null $id, array $produciton_settings, NewDatabase $database) {
        $clockSpeed = $produciton_settings['clock_speed'];
        $useSomersloop = $produciton_settings['use_somersloop'] ? 1 : 0;

        if ($id) {
            $database->update(table: "production_settings", columns: ['clock_speed', 'use_somersloop'], values: [$clockSpeed, $useSomersloop], where: ['id' => $id]);
        } else {
            $database->insert(table: "production_settings", columns: ['clock_speed', 'use_somersloop'], values: [$clockSpeed, $useSomersloop]);
            $id = $database->lastInsertId();
        }

        return $id;
    }

    private static function validateAccess(int $gameSaveId, int $productionLineId, int $userId): bool {
        $visable = self::checkProductionLineVisability($gameSaveId, $productionLineId, $userId);
        $hasAccess = GameSaves::checkAccess($gameSaveId, $userId, Role::FACTORY_WORKER, negate: true);

        return $visable && $hasAccess;
    }


}
