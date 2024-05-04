<?php

class Recipes
{

    public static function getAllRecipes()
    {
        return Database::getAll("recipes", ['*'], [], [], 'name ASC');
    }

    public static function getRecipeById(int $id)
    {
        return Database::get("recipes", ['recipes.*', 'items.name as itemName'], ['items' => 'items.id = recipes.item_id'], ['recipes.id' => $id]);
    }

}