<?php

class DocsData {
    // Public properties
    public array $recipes = [];
    public array $buildings = [];
    public array $items = [];
    public array $added_stuff = ['items' => [], 'buildings' => [], 'recipes' => []];
    public array $updated_stuff = ['items' => [], 'buildings' => [], 'recipes' => []];
    public array $deleted_stuff = ['items' => [], 'buildings' => [], 'recipes' => []];

    // Private properties
    private mixed $jsonData;
    private array $itemsNativeClasses;
    private array $BuildingNativeClasses;
    private array $buildingClasses;
    private int $itemI = 1;
    private int $buildingI = 1;
    private int $recipeI = 1;
    private int $recipeIngredientI = 1;

    // Database
    private NewDatabase $database;

    // Constructor
    function __construct(mixed $jsonData, array $itemsNativeClasses, array $BuildingNativeClasses) {
        if (empty($jsonData)) {
            throw new Exception('Json data is empty');
        }

        $this->jsonData = $jsonData;
        $this->itemsNativeClasses = $itemsNativeClasses;
        $this->BuildingNativeClasses = $BuildingNativeClasses;
        $this->database = new NewDatabase();

        $this->setItems();
        $this->setBuildings();
        $this->setRecipes();
    }

    /**
     * Item Functions
     */

    private function setItems() {
        foreach ($this->jsonData as $data) {
            $nativeClass = $this->extractNativeClass($data['NativeClass']);

            if ($this->isItemClass($nativeClass)) {
                $this->addItems($data['Classes']);
            }
        }
    }

    /**
     * Extract the native class from the string
     * @param string $nativeClass
     * @return string
     */
    private function extractNativeClass($nativeClass) {
        $item_name = explode('.', $nativeClass)[2];
        $item_name = str_replace("'", '', $item_name);
        return $item_name;
    }

    private function isItemClass($nativeClass): bool {
        return in_array($nativeClass, $this->itemsNativeClasses);
    }

    private function addItems(array $classes): void {
        foreach ($classes as $itemData) {
            $this->items[] = $this->createItem($itemData);
        }
    }


    /**
     * Building Functions
     */

    private function createItem(array $itemData): Item {
        return new Item(
            $this->itemI,
            $itemData['mDisplayName'],
            $itemData['mForm'],
            $itemData['ClassName']
        );
    }

    /**
     * Set buildings from json data
     *
     * @return void
     */
    private function setBuildings() {
        foreach ($this->jsonData as $data) {
            $nativeClass = $this->extractNativeClass($data['NativeClass']);

            if ($this->isBuildingClass($nativeClass)) {
                foreach ($data['Classes'] as $buildingData) {
                    $powerConsumption = $this->getPowerConsumption($buildingData);
                    $powerProduction = $this->getPowerProduction($buildingData);

                    $this->addBuilding($buildingData, $powerConsumption, $powerProduction);
                }
            }
        }
    }

    private function isBuildingClass($nativeClass): bool {
        return in_array($nativeClass, $this->BuildingNativeClasses);
    }

    private function getPowerConsumption(array $buildingData): int {
        return $buildingData['mEstimatedMaximumPowerConsumption'] ??
            $buildingData['mPowerConsumption'] ??
            0;
    }

    private function getPowerProduction(array $buildingData): int {
        return $buildingData['mBasePowerProduction'] ??
            $buildingData['mPowerProduction'] ??
            0;
    }

    private function addBuilding(array $buildingData, int $powerConsumption, int $powerProduction): void {
        $this->buildings[] = new Building(
            $this->buildingI,
            $buildingData['mDisplayName'],
            $buildingData['ClassName'],
            $powerConsumption,
            $powerProduction
        );

        $this->buildingClasses[] = $buildingData['ClassName'];
        $this->buildingI++;
    }

    /**
     * Recipe Functions
     */

    private function setRecipes() {
        foreach ($this->jsonData as $data) {
            $nativeClass = $this->extractNativeClass($data['NativeClass']);

            if ($nativeClass === 'FGRecipe') {
                $this->processRecipes($data['Classes']);
            }
        }
    }

    private function processRecipes(array $recipesData): void {
        foreach ($recipesData as $recipeData) {
            $producedIn = $this->extractBuildNames($recipeData['mProducedIn']);

            foreach ($producedIn as $buildingClass) {
                if (in_array($buildingClass, $this->buildingClasses)) {
                    $this->createRecipe($recipeData, $buildingClass);
                }
            }
        }
    }

    private function extractBuildNames($producedIn): array {
        preg_match_all('/Build_[^\/]+_C/', $producedIn, $matches);
        return array_map(function ($building) {
            return explode('.', $building)[1]; // Get the part after '.'
        }, $matches[0]);
    }

    private function createRecipe(array $recipeData, string $buildingClass): void {
        $duration = floatval($recipeData['mManufactoringDuration']) / 60;
        $producedItems = $this->extractProductsAndAmounts($recipeData['mProduct'], $this->items);
        $recipeIngredients = $this->extractProductsAndAmounts($recipeData['mIngredients'], $this->items);

        if (empty($producedItems) || $producedItems[0][0] === null) {
            return;
        }

        $recipeIngredientsList = $this->createRecipeIngredients($recipeIngredients, $duration);

        $this->adjustProducedItems($producedItems);

        $exportPerMin = $this->calculateExportPerMin($producedItems, $duration, 0);
        $secondExportPerMin = $this->calculateExportPerMin($producedItems, $duration, 1);
        $display_name = $recipeData['mDisplayName'];
        // if in display name is `Alternate: ` remove it and place at the end `(Alternate)`
        if (strpos($display_name, 'Alternate: ') === 0) {
            $display_name = substr($display_name, 11) . ' (Alternate)';
        }
        $this->recipes[] = new Recipe(
            $this->recipeI,
            $display_name,
            $recipeData['ClassName'],
            $this->buildings[array_search($buildingClass, $this->buildingClasses)],
            $producedItems[0][0],
            $producedItems[1][0] ?? null,
            $exportPerMin,
            $secondExportPerMin,
            $recipeIngredientsList
        );

        $this->recipeI++;
    }

    private function extractProductsAndAmounts($products, $items): array {
        preg_match_all('/Desc_[^\/]+_C/', $products, $productMatches);
        preg_match_all('/Amount=(\d+)/', $products, $amountMatches);

        $amounts = array_map('intval', $amountMatches[1]);
        $products = [];

        foreach ($productMatches[0] as $product) {
            $productName = explode('.', $product)[1];
            foreach ($items as $itemData) {
                if ($itemData->class_name === $productName) {
                    $products[] = $itemData;
                    break;
                }
            }
        }

        return array_map(null, $products, $amounts);
    }

    private function createRecipeIngredients(array $recipeIngredients, float $duration): array {
        $recipeIngredientsList = [];

        foreach ($recipeIngredients as $ingredient) {
            $amount = $ingredient[1];

            if ($ingredient[0]->form === 'RF_LIQUID' || $ingredient[0]->form === 'RF_GAS') {
                $amount /= 1000;
            }

            $recipeIngredientsList[] = new recipeIngredient(
                $this->recipeIngredientI,
                $this->recipeI,
                $ingredient[0]->class_name,
                $ingredient[0]->id,
                $amount / $duration
            );

            $this->recipeIngredientI++;
        }

        return $recipeIngredientsList;
    }

    private function adjustProducedItems(array &$producedItems): void {
        foreach ($producedItems as $index => &$producedItem) {
            if ($producedItem[0]->form === 'RF_LIQUID' || $producedItem[0]->form === 'RF_GAS') {
                $producedItem[1] /= 1000;
            }
        }
    }

    /**
     * Database Functions
     */


    /**
     * Insert new items into the database and update existing ones
     *
     * @return void
     */
    public function insertItems(): void {
        $this->database->beginTransaction();
        $processedItemClassNames = [];
        try {
            foreach ($this->items as $item) {
                $processedItemClassNames[] = $item->class_name;

                $existing = $this->database->get(table: 'items', columns: ['id'], where: ['class_name' => $item->class_name]);

                if ($existing) {
                    $this->database->update(
                        table:   'items',
                        columns: ['name', 'form', 'class_name'],
                        values:  [$item->name, $item->form, $item->class_name],
                        where:   ['id' => $existing->id],
                    );
                    $this->updated_stuff['items'][] = ['name' => $item->name];
                } else {
                    $this->database->insert(
                        table:   'items',
                        columns: ['name', 'form', 'class_name'],
                        values:  [$item->name, $item->form, $item->class_name],
                    );
                    $this->added_stuff['items'][] = ['name' => $item->name];
                }
            }

            if (empty($processedItemClassNames)) {
                return;
            }

            $existingItems = $this->database->getAll(table: 'items', columns: ['class_name'], fetchStyle: PDO::FETCH_COLUMN);


            $itemsToDelete = array_diff($existingItems, $processedItemClassNames);

            foreach ($itemsToDelete as $class_name) {
                $this->database->delete(
                    table: 'items',
                    where: ['class_name' => $class_name],
                );
                $this->deleted_stuff['items'][] = ['name' => $class_name];
            }

            $this->database->commit();
        } catch (Exception $e) {
            $this->database->rollBack();
            throw $e;
        }
    }

    /**
     * Insert new buildings into the database and update existing ones
     *
     * @return void
     */
    public function insertBuildings(): void {
        $this->database->beginTransaction();
        try {
            foreach ($this->buildings as $building) {
                $processedBuildingClassNames[] = $building->class_name;

                $existing = $this->database->get(table: 'buildings', columns: ['id'], where: ['class_name' => $building->class_name]);

                if ($existing) {
                    $this->database->update(
                        table:   'buildings',
                        columns: ['name', 'class_name', 'power_used', 'power_generation'],
                        values:  [$building->name, $building->class_name, $building->power_used, $building->power_produced],
                        where:   ['id' => $existing->id],
                    );
                    $this->updated_stuff['buildings'][] = ['name' => $building->name];
                } else {
                    $this->database->insert(
                        table:   'buildings',
                        columns: ['name', 'class_name', 'power_used', 'power_generation'],
                        values:  [$building->name, $building->class_name, $building->power_used, $building->power_produced],
                    );
                    $this->added_stuff['buildings'][] = ['name' => $building->name];
                }
            }

            if (empty($processedBuildingClassNames)) {
                return;
            }

            $existingBuildings = $this->database->getAll(table: 'buildings', columns: ['class_name'], fetchStyle: PDO::FETCH_COLUMN);
            $buildingsToDelete = array_diff($existingBuildings, $processedBuildingClassNames);

            foreach ($buildingsToDelete as $class_name) {
                $this->database->delete(
                    table: 'buildings',
                    where: ['class_name' => $class_name],
                );
                $this->deleted_stuff['buildings'][] = ['name' => $class_name];
            }

            $this->database->commit();
        } catch (Exception $e) {
            $this->database->rollBack();
            throw $e;
        }
    }

    /**
     * Insert new recipes into the database
     *
     * @return void
     */
    public function insertRecipes(): void {
        $this->database->beginTransaction();
        try {
            $this->database->query('DELETE FROM recipe_ingredients');
            $recipeIngredientI = 1;
            foreach ($this->recipes as $recipe) {
                $processedRecipeClassNames[] = $recipe->class_name;

                $existing = $this->database->get(table: 'recipes', columns: ['id'], where: ['class_name' => $recipe->class_name]);
                $itemId = $this->database->get(table: 'items', columns: ['id'], where: ['class_name' => $recipe->itemId->class_name]);
                if ($recipe->secondItemId) {
                    $secondItemId = $this->database->get(table: 'items', columns: ['id'], where: ['class_name' => $recipe->secondItemId->class_name])->id;
                } else {
                    $secondItemId = null;
                }
                $buildingId = $this->database->get(table: 'buildings', columns: ['id'], where: ['class_name' => $recipe->buildingId->class_name]);
                if ($existing) {
                    $this->database->update(
                        table:   'recipes',
                        columns: ['name', 'class_name', 'buildings_id', 'item_id', 'item_id2', 'export_amount_per_min', 'export_amount_per_min2'],
                        values:  [$recipe->name, $recipe->class_name, $buildingId->id, $itemId->id, $secondItemId, $recipe->exportAmountPerMin, $recipe->secondExportAmountPerMin],
                        where:   ['id' => $existing->id],
                    );
                    $this->updated_stuff['recipes'][] = ['name' => $recipe->name];
                    $recipeId = $existing->id;
                } else {
                    $this->database->insert(
                        table:   'recipes',
                        columns: ['name', 'class_name', 'buildings_id', 'item_id', 'item_id2', 'export_amount_per_min', 'export_amount_per_min2'],
                        values:  [$recipe->name, $recipe->class_name, $buildingId->id, $itemId->id, $secondItemId, $recipe->exportAmountPerMin, $recipe->secondExportAmountPerMin],
                    );
                    $this->added_stuff['recipes'][] = ['name' => $recipe->name];
                    $recipeId = $this->database->lastInsertId();
                }


                // handel recipe ingredients
                foreach ($recipe->ingredients as $ingredient) {
                    $itemId = $this->database->get(table: 'items', columns: ['id'], where: ['class_name' => $ingredient->itemClass]);
                    $this->database->insert(
                        table:   'recipe_ingredients',
                        columns: ['id', 'recipes_id', 'items_id', 'import_amount_per_min'],
                        values:  [$recipeIngredientI, $recipeId, $itemId->id, $ingredient->importAmountPerMin],
                    );
                    $recipeIngredientI++;
                }
            }

            if (empty($processedRecipeClassNames)) {
                return;
            }

            $existingRecipes = $this->database->getAll(table: 'recipes', columns: ['class_name'], fetchStyle: PDO::FETCH_COLUMN);
            $recipesToDelete = array_diff($existingRecipes, $processedRecipeClassNames);

            foreach ($recipesToDelete as $class_name) {
                $this->database->delete(
                    table: 'recipes',
                    where: ['class_name' => $class_name],
                );
                $this->deleted_stuff['recipes'][] = ['name' => $class_name];
            }

            $this->database->commit();
        } catch (Exception $e) {
            $this->database->rollBack();
            throw $e;

        }
    }


    /**
     * General Functions
     */

    private function calculateExportPerMin(array $producedItems, float $duration, int $index): ?float {
        return isset($producedItems[$index]) ? $producedItems[$index][1] / $duration : null;
    }


}