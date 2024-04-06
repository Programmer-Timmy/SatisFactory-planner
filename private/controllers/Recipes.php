<?php

class Recipes
{

    public static function getAllRecipes()
    {
        return Database::getAll("recipes", ['*'], [], [], 'name ASC');
    }

}