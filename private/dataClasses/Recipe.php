<?php

class Recipe
{
    public $id;
    public $name;
    public $class_name;
    public $buildingId; // Instance of Building class
    public $itemId; // Instance of Item class
    public $secondItemId; // Instance of Item class
    public $exportAmountPerMin;
    public $secondExportAmountPerMin;
    public $ingredients; // Array of RecipeIngredient instances

    // Constructor to initialize properties
    public function __construct($id, $name, $class_name, Building $buildingId, Item $itemId, Item|null $secondItemId, $exportAmountPerMin, $secondExportAmountPerMin, array $ingredients)
    {
        $this->id = $id;
        $this->name = $name;
        $this->class_name = $class_name;
        $this->buildingId = $buildingId;
        $this->itemId = $itemId;
        $this->secondItemId = $secondItemId;
        $this->exportAmountPerMin = $exportAmountPerMin;
        $this->secondExportAmountPerMin = $secondExportAmountPerMin;
        $this->ingredients = $ingredients;
    }
}
