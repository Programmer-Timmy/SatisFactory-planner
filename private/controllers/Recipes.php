<?php

class Recipes
{

    public static function getAllRecipes()
    {
        return Database::getAll("recipes", ['*'], [], [], 'name ASC');
    }

    public static function getRecipeById(int $id)
    {
        return Database::get("recipes", ['*'], [], ['id' => $id]);
    }

}