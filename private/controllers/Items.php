<?php

class Items
{

    public static function getAllItems()
    {
        return Database::getAll("items" , ['*'], [], [], 'name ASC');
    }

    public static function getItemById($id)
    {
        return Database::get("items", ['*'],[], ['id' => $id]);
    }

    public static function getAllItemsPRecipe() {
        $items = self::getAllItems();
        foreach ($items as $item) {
            $item->recipes = Recipes::getRecipeByItemId($item->id);
        }
        return $items;
    }

}