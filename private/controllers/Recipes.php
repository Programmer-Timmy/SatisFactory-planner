<?php

class Recipes {


    public static function getAllRecipes() {
        $sql = "
SELECT
    r.*,
    COALESCE(ing.ingredients, JSON_ARRAY()) AS ingredients,
    COALESCE(bld.building,   JSON_ARRAY()) AS building,
    CASE
      WHEN i3.id IS NOT NULL THEN JSON_ARRAY(
        JSON_OBJECT(
          'id', i2.id, 'name', i2.name, 'class_name', i2.class_name,
          'form', i2.form, 'quantity', r.export_amount_per_min
        ),
        JSON_OBJECT(
          'id', i3.id, 'name', i3.name, 'class_name', i3.class_name,
          'form', i3.form, 'quantity', r.export_amount_per_min2
        )
      )
      ELSE JSON_ARRAY(
        JSON_OBJECT(
          'id', i2.id, 'name', i2.name, 'class_name', i2.class_name,
          'form', i2.form, 'quantity', r.export_amount_per_min
        )
      )
    END AS products
FROM recipes r
LEFT JOIN (
  SELECT
      ri.recipes_id,
      JSON_ARRAYAGG(JSON_OBJECT(
          'id', i.id,
          'name', i.name,
          'class_name', i.class_name,
          'form', i.form,
          'quantity', ri.import_amount_per_min
      )) AS ingredients
  FROM recipe_ingredients ri
  INNER JOIN items i ON i.id = ri.items_id
  GROUP BY ri.recipes_id
) ing ON ing.recipes_id = r.id
LEFT JOIN (
  SELECT
      r2.id AS recipes_id,
      IF(b.id IS NOT NULL,
         JSON_ARRAY(JSON_OBJECT(
            'id', b.id,
            'name', b.name,
            'class_name', b.class_name,
            'power_used', b.power_used,
            'power_generated', b.power_generation
         )),
         JSON_ARRAY()
      ) AS building
  FROM recipes r2
  LEFT JOIN buildings b ON b.id = r2.buildings_id
) bld ON bld.recipes_id = r.id
LEFT JOIN items i2 ON i2.id = r.item_id
LEFT JOIN items i3 ON i3.id = r.item_id2
ORDER BY LTRIM(SUBSTRING_INDEX(r.name, '(', 1)) ASC;
";
        $database = new Database();
        $recipes = $database->query($sql);

        foreach ($recipes as $recipe) {
            $recipe->ingredients = json_decode($recipe->ingredients) ?: [];
            $recipe->building = json_decode($recipe->building) ?: [];
            $recipe->products = json_decode($recipe->products) ?: [];
        }

        return $recipes;
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

    private static function getRecipeQuery($where = '') {
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
              LEFT JOIN items i2 ON r.item_id2 = i2.id"
            . ($where ? " WHERE $where" : '');

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

    public static function getRecipeByItemId($id) {
        $recipesIds = Database::query("SELECT id from recipes WHERE item_id = :id OR item_id2 = :id", ['id' => $id]);
        foreach ($recipesIds as $recipeId) {
            $recipeId->url = "/api/recipes?id=" . $recipeId->id;
        }
        return $recipesIds;
    }


}