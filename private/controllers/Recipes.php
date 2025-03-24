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

    public static function fetchRecipesWithDetails() {
        $database = new NewDatabase();

        $query = self::getRecipeQuery();
        $recipes = $database->query($query);

        return array_map([self::class, 'processRecipe'], $recipes);
    }

    public static function getRecipeWithDetails(int $id) {
        $database = new NewDatabase();

        $query = self::getRecipeQuery() . " WHERE r.id = :id LIMIT 1";
        $recipes = $database->query($query, ['id' => $id]);

        return $recipes ? self::processRecipe($recipes[0]) : null;
    }

    private static function getRecipeQuery() {
        return "SELECT 
                r.id AS recipe_id, 
                r.name AS recipe_name, 
                r.class_name as class_name,
                
                -- Gebouwgegevens
                b.id AS building_id, 
                b.name AS building_name, 
                b.class_name as building_class_name,
                b.power_used AS building_power_used,
                b.power_generation AS building_power_generated,
                
                -- Item 1 gegevens
                i1.id AS item1_id, 
                i1.name AS item1_name, 
                i1.class_name as item1_class_name,
                i1.form as item1_form,
                r.export_amount_per_min AS item1_quantity,
                
                -- Item 2 gegevens
                i2.id AS item2_id, 
                i2.name AS item2_name,
                i2.class_name as item2_class_name,
                i2.form as item2_form,
                r.export_amount_per_min2 AS item2_quantity

              FROM recipes r
              LEFT JOIN buildings b ON r.buildings_id = b.id
              LEFT JOIN items i1 ON r.item_id = i1.id
              LEFT JOIN items i2 ON r.item_id2 = i2.id";
    }

    private static function processRecipe($recipe) {
        // Maak een building-object
        $recipe->building = [
            'id' => $recipe->building_id,
            'name' => $recipe->building_name,
            'class_name' => $recipe->building_class_name,
            'power_used' => $recipe->building_power_used,
            'power_generated' => $recipe->building_power_generated,
        ];

        // Verwijder losse building-velden
        unset($recipe->building_id, $recipe->building_name, $recipe->building_class_name, $recipe->building_power_used, $recipe->building_power_generated);

        // Maak een outputs-array
        $recipe->outputs = array_filter([
                                            $recipe->item1_id ? [
                                                'id' => $recipe->item1_id,
                                                'name' => $recipe->item1_name,
                                                'class_name' => $recipe->item1_class_name,
                                                'form' => $recipe->item1_form,
                                                'quantity' => $recipe->item1_quantity
                                            ] : null,
                                            $recipe->item2_id ? [
                                                'id' => $recipe->item2_id,
                                                'name' => $recipe->item2_name,
                                                'class_name' => $recipe->item2_class_name,
                                                'form' => $recipe->item2_form,
                                                'quantity' => $recipe->item2_quantity
                                            ] : null
                                        ]);

        // Verwijder losse item-velden
        unset($recipe->item1_id, $recipe->item1_name, $recipe->item1_quantity, $recipe->item1_class_name, $recipe->item1_form);
        unset($recipe->item2_id, $recipe->item2_name, $recipe->item2_quantity, $recipe->item2_class_name, $recipe->item2_form);

        // Resources ophalen en toevoegen
        $recipe->resources = self::getRecipeResources($recipe->recipe_id);

        return $recipe;
    }



}