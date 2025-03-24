<?php

class Recipes {

    public static function getAllRecipes() {
        return Database::getAll("recipes", ['*'], [], [], 'LTRIM(SUBSTRING_INDEX(`name`, "(", 1)) ASC');
    }

    public static function getRecipeById(int $id) {
        return Database::get("recipes", ['recipes.*', 'items.name as itemName', 'items2.name as secondItemName'], ['items' => 'items.id = recipes.item_id left join items as items2 on items2.id = recipes.item_id2'], ['recipes.id' => $id]);
    }

    public static function getRecipeByIdAjax(int $id) {
        return Database::get(
            "recipes",
            ['recipes.*', 'items.name as itemName', 'items2.name as secondItemName'],
            ['items' => 'items.id = recipes.item_id left join items as items2 on items2.id = recipes.item_id2'],
            ['recipes.id' => $id]);
    }

    public static function checkIfMultiOutput(int $id) {
        $recipe = Database::get("recipes", ['item_id', 'item_id2'], [], ['id' => $id]);
        if ($recipe->item_id2) {
            return true;
        }
        return false;
    }

    public static function getRecipeResources(int $id) {
        return Database::getAll("recipe_ingredients", ['recipes_id as recipeId', 'items_id as itemId', 'items.name as name', 'import_amount_per_min as importAmount'], ['items' => 'items.id = recipe_ingredients.items_id'], ['recipes_id' => $id]);
    }

    public static function getAllRecipeWithResources() {
        $recipes = self::getAllRecipes();
        $cache = [];
        $buildingCache = [];

        foreach ($recipes as $recipe) {
            // Item 1 ophalen met caching
            $item1 = $cache[$recipe->item_id] ??= Items::getItemById($recipe->item_id);
            $item1->quantity = $recipe->export_amount_per_min;

            // Gebouw ophalen met caching
            $recipe->building = $buildingCache[$recipe->buildings_id] ??= Buildings::getBuildingById($recipe->buildings_id);

            // Item 2 ophalen als het bestaat
            $item2 = null;
            if ($recipe->item_id2) {
                $item2 = $cache[$recipe->item_id2] ??= Items::getItemById($recipe->item_id2);
                $item2->quantity = $recipe->export_amount_per_min2;
            }

            // Alleen niet-null outputs behouden
            $recipe->outputs = array_filter([$item1, $item2], fn($item) => $item !== null);

            // Overbodige properties verwijderen
            unset(
                $recipe->item_id,
                $recipe->item_id2,
                $recipe->export_amount_per_min,
                $recipe->export_amount_per_min2,
                $recipe->building_id
            );

            // Resources ophalen en toevoegen
            $recipe->resources = self::getRecipeResources($recipe->id);
        }
        return $recipes;
    }

    public static function getRecipeWithResources(int $id) {
        try {
            $recipe = self::getRecipeById($id);
            if (!$recipe) {
                return null;
            }
            $recipe->resources = self::getRecipeResources($id);

            $item1 = Items::getItemById($recipe->item_id);
            $item1->quantity = $recipe->export_amount_per_min;

            // Gebouw ophalen met caching
            $recipe->building = Buildings::getBuildingById($recipe->buildings_id);

            // Item 2 ophalen als het bestaat
            $item2 = null;
            if ($recipe->item_id2) {
                $item2 = Items::getItemById($recipe->item_id2);
                $item2->quantity = $recipe->export_amount_per_min2;
            }

            // Alleen niet-null outputs behouden
            $recipe->outputs = array_filter([$item1, $item2], fn($item) => $item !== null);

            // Overbodige properties verwijderen
            unset(
                $recipe->item_id,
                $recipe->item_id2,
                $recipe->export_amount_per_min,
                $recipe->export_amount_per_min2,
                $recipe->building_id
            );

            // Resources ophalen en toevoegen
            $recipe->resources = self::getRecipeResources($recipe->id);

            return $recipe;
        } catch (Exception $e) {
            return null;
        }
    }


}