<?php

class Recipes
{

    public static function getAllRecipes()
    {
        return Database::getAll("recipes", ['*'], [], [], 'LTRIM(SUBSTRING_INDEX(`name`, "(", 1)) ASC');
    }

    public static function getRecipeById(int $id)
    {
        return Database::get("recipes", ['recipes.*', 'items.name as itemName', 'items2.name as secondItemName'], ['items' => 'items.id = recipes.item_id left join items as items2 on items2.id = recipes.item_id2'], ['recipes.id' => $id]);
    }

    public static function checkIfMultiOutput(int $id)
    {
        $recipe = Database::get("recipes", ['item_id', 'item_id2'], [], ['id' => $id]);
        if ($recipe->item_id2) {
            return true;
        }
        return false;
    }

    public static function getRecipeResources(int $id)
    {
        return Database::getAll("recipe_ingredients", ['recipes_id as recipeId', 'items_id as itemId', 'items.name as name', 'import_amount_per_min as importAmount'], ['items' => 'items.id = recipe_ingredients.items_id'], ['recipes_id' => $id]);
    }

    public static function getAllRecipeWithResources()
    {
        $recipes = self::getAllRecipes();
        foreach ($recipes as $recipe) {
            $recipe->resources = self::getRecipeResources($recipe->id);
        }
        return $recipes;
    }

    public static function getRecipeWithResources(int $id)
    {
        $recipe = self::getRecipeById($id);
        $recipe->resources = self::getRecipeResources($id);
        return $recipe;
    }
}