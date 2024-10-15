<?php

class RecipeIngredient
{
    public $id;
    public $recipeId;
    public $itemClass;
    public $itemId;
    public $importAmountPerMin;

    // Constructor to initialize properties
    public function __construct($id, $recipeId, $itemClass, $itemId, $importAmountPerMin)
    {
        $this->id = $id;
        $this->recipeId = $recipeId;
        $this->itemClass = $itemClass;
        $this->itemId = $itemId;
        $this->importAmountPerMin = $importAmountPerMin;
    }
}
