<?php

class Recipes
{

    public static function getAllRecipes()
    {
        return Database::getAll("recipes", ['*'], [], [], 'name ASC');
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
}