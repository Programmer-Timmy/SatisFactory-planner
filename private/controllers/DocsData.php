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
    private array $buildingClasses = [];
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

    public function getPreviewChanges(): array {
        $preview = [
            'added_stuff' => ['items' => [], 'buildings' => [], 'recipes' => []],
            'updated_stuff' => ['items' => [], 'buildings' => [], 'recipes' => []],
            'deleted_stuff' => ['items' => [], 'buildings' => [], 'recipes' => []],
        ];

        $processedItemClassNames = [];
        foreach ($this->items as $item) {
            $processedItemClassNames[] = $item->class_name;

            $existing = $this->database->get(table: 'items', columns: ['id', 'name', 'form', 'class_name'], where: ['class_name' => $item->class_name]);

            if ($existing) {
                $changes = $this->getItemChanges($existing, $item);

                if (!empty($changes)) {
                    $preview['updated_stuff']['items'][] = ['name' => $item->name, 'changes' => $changes];
                }
            } elseif (!$existing) {
                $preview['added_stuff']['items'][] = ['name' => $item->name];
            }
        }

        if (!empty($processedItemClassNames)) {
            $existingItems = $this->database->getAll(table: 'items', columns: ['name', 'class_name']);
            $existingItemClassNames = array_map(
                static fn($item) => $item->class_name,
                array_filter($existingItems ?: [], fn($item) => !$this->shouldExcludeByNameOrClassName($item->name, $item->class_name))
            );
            $itemsToDelete = array_diff($existingItemClassNames, $processedItemClassNames);

            foreach ($itemsToDelete as $class_name) {
                $preview['deleted_stuff']['items'][] = ['name' => $class_name];
            }
        }

        $processedBuildingClassNames = [];
        foreach ($this->buildings as $building) {
            $processedBuildingClassNames[] = $building->class_name;

            $existing = $this->database->get(table: 'buildings', columns: ['id', 'name', 'class_name', 'power_used', 'power_generation'], where: ['class_name' => $building->class_name]);

            if ($existing) {
                $changes = $this->getBuildingChanges($existing, $building);

                if (!empty($changes)) {
                    $preview['updated_stuff']['buildings'][] = ['name' => $building->name, 'changes' => $changes];
                }
            } elseif (!$existing) {
                $preview['added_stuff']['buildings'][] = ['name' => $building->name];
            }
        }

        $processedRecipeClassNames = [];
        foreach ($this->recipes as $recipe) {
            $processedRecipeClassNames[] = $recipe->class_name;

            $existing = $this->database->get(table: 'recipes', columns: ['id', 'name', 'class_name', 'buildings_id', 'item_id', 'item_id2', 'export_amount_per_min', 'export_amount_per_min2'], where: ['class_name' => $recipe->class_name]);

            if ($existing) {
                $changes = $this->getRecipeChanges($existing, $recipe);

                if (!empty($changes)) {
                    $preview['updated_stuff']['recipes'][] = ['name' => $recipe->name, 'changes' => $changes];
                }
            } else {
                $preview['added_stuff']['recipes'][] = ['name' => $recipe->name];
            }
        }

        if (!empty($processedRecipeClassNames)) {
            $existingRecipes = $this->database->getAll(table: 'recipes', columns: ['name', 'class_name']);
            $existingRecipeClassNames = array_map(
                static fn($recipe) => $recipe->class_name,
                array_filter($existingRecipes ?: [], fn($recipe) => !$this->shouldExcludeByNameOrClassName($recipe->name, $recipe->class_name))
            );
            $recipesToDelete = array_diff($existingRecipeClassNames, $processedRecipeClassNames);

            foreach ($recipesToDelete as $class_name) {
                $preview['deleted_stuff']['recipes'][] = ['name' => $class_name];
            }
        }

        return $preview;
    }

    /**
     * Item Functions
     */

    private function setItems() {
        foreach ($this->jsonData as $data) {
            $this->assertDocsEntry($data);
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
        $parts = explode('.', $nativeClass);

        if (count($parts) < 3) {
            throw new InvalidArgumentException('Invalid Docs NativeClass value');
        }

        $item_name = $parts[2];
        $item_name = str_replace("'", '', $item_name);
        return $item_name;
    }

    private function isItemClass($nativeClass): bool {
        return in_array($nativeClass, $this->itemsNativeClasses);
    }

    private function addItems(array $classes): void {
        foreach ($classes as $itemData) {
            if ($this->shouldExcludeDocsClassData($itemData)) {
                continue;
            }

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
            $this->assertDocsEntry($data);
            $nativeClass = $this->extractNativeClass($data['NativeClass']);

            if ($this->isBuildingClass($nativeClass)) {
                foreach ($data['Classes'] as $buildingData) {
                    if ($this->shouldExcludeDocsClassData($buildingData)) {
                        continue;
                    }

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
            $this->assertDocsEntry($data);
            $nativeClass = $this->extractNativeClass($data['NativeClass']);

            if ($nativeClass === 'FGRecipe') {
                $this->processRecipes($data['Classes']);
            }
        }
    }

    private function processRecipes(array $recipesData): void {
        foreach ($recipesData as $recipeData) {
            if ($this->shouldExcludeDocsClassData($recipeData)) {
                continue;
            }

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

            $recipeIngredientsList[] = new RecipeIngredient(
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

                $existing = $this->database->get(table: 'items', columns: ['id', 'name', 'form', 'class_name'], where: ['class_name' => $item->class_name]);

                $changes = $existing ? $this->getItemChanges($existing, $item) : [];

                if ($existing && !empty($changes)) {
                    $this->database->update(
                        table:   'items',
                        columns: ['name', 'form', 'class_name'],
                        values:  [$item->name, $item->form, $item->class_name],
                        where:   ['id' => $existing->id],
                    );
                    $this->updated_stuff['items'][] = ['name' => $item->name, 'changes' => $changes];
                } elseif (!$existing) {
                    $this->database->insert(
                        table:   'items',
                        columns: ['name', 'form', 'class_name'],
                        values:  [$item->name, $item->form, $item->class_name],
                    );
                    $this->added_stuff['items'][] = ['name' => $item->name];
                }
            }

            if (empty($processedItemClassNames)) {
                $this->database->commit();
                return;
            }

            $existingItems = $this->database->getAll(table: 'items', columns: ['name', 'class_name']);


            $existingItemClassNames = array_map(
                static fn($item) => $item->class_name,
                array_filter($existingItems ?: [], fn($item) => !$this->shouldExcludeByNameOrClassName($item->name, $item->class_name))
            );
            $itemsToDelete = array_diff($existingItemClassNames, $processedItemClassNames);

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

                $existing = $this->database->get(table: 'buildings', columns: ['id', 'name', 'class_name', 'power_used', 'power_generation'], where: ['class_name' => $building->class_name]);

                $changes = $existing ? $this->getBuildingChanges($existing, $building) : [];

                if ($existing && !empty($changes)) {
                    $this->database->update(
                        table:   'buildings',
                        columns: ['name', 'class_name', 'power_used', 'power_generation'],
                        values:  [$building->name, $building->class_name, $building->power_used, $building->power_produced],
                        where:   ['id' => $existing->id],
                    );
                    $this->updated_stuff['buildings'][] = ['name' => $building->name, 'changes' => $changes];
                } elseif (!$existing) {
                    $this->database->insert(
                        table:   'buildings',
                        columns: ['name', 'class_name', 'power_used', 'power_generation'],
                        values:  [$building->name, $building->class_name, $building->power_used, $building->power_produced],
                    );
                    $this->added_stuff['buildings'][] = ['name' => $building->name];
                }
            }

            if (empty($processedBuildingClassNames)) {
                $this->database->commit();
                return;
            }
//
//            $existingBuildings = $this->database->getAll(table: 'buildings', columns: ['class_name'], fetchStyle: PDO::FETCH_COLUMN);
//            $buildingsToDelete = array_diff($existingBuildings, $processedBuildingClassNames);
//
//            foreach ($buildingsToDelete as $class_name) {
//                $this->database->delete(
//                    table: 'buildings',
//                    where: ['class_name' => $class_name],
//                );
//                $this->deleted_stuff['buildings'][] = ['name' => $class_name];
//            }

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
            $recipeChangesByClassName = [];

            foreach ($this->recipes as $recipe) {
                $existing = $this->database->get(table: 'recipes', columns: ['id', 'name', 'class_name', 'buildings_id', 'item_id', 'item_id2', 'export_amount_per_min', 'export_amount_per_min2'], where: ['class_name' => $recipe->class_name]);

                if ($existing) {
                    $recipeChangesByClassName[$recipe->class_name] = $this->getRecipeChanges($existing, $recipe);
                }
            }

            $this->database->query('DELETE FROM recipe_ingredients');
            $recipeIngredientI = 1;
            foreach ($this->recipes as $recipe) {
                $processedRecipeClassNames[] = $recipe->class_name;

                $existing = $this->database->get(table: 'recipes', columns: ['id', 'name', 'class_name', 'buildings_id', 'item_id', 'item_id2', 'export_amount_per_min', 'export_amount_per_min2'], where: ['class_name' => $recipe->class_name]);
                $itemId = $this->database->get(table: 'items', columns: ['id'], where: ['class_name' => $recipe->itemId->class_name]);
                if ($recipe->secondItemId) {
                    $secondItemId = $this->database->get(table: 'items', columns: ['id'], where: ['class_name' => $recipe->secondItemId->class_name])->id;
                } else {
                    $secondItemId = null;
                }
                $buildingId = $this->database->get(table: 'buildings', columns: ['id'], where: ['class_name' => $recipe->buildingId->class_name]);
                if ($existing) {
                    $changes = $recipeChangesByClassName[$recipe->class_name] ?? [];
                    $this->database->update(
                        table:   'recipes',
                        columns: ['name', 'class_name', 'buildings_id', 'item_id', 'item_id2', 'export_amount_per_min', 'export_amount_per_min2'],
                        values:  [$recipe->name, $recipe->class_name, $buildingId->id, $itemId->id, $secondItemId, $recipe->exportAmountPerMin, $recipe->secondExportAmountPerMin],
                        where:   ['id' => $existing->id],
                    );
                    if (!empty($changes)) {
                        $this->updated_stuff['recipes'][] = ['name' => $recipe->name, 'changes' => $changes];
                    }
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
                $this->database->commit();
                return;
            }

            $existingRecipes = $this->database->getAll(table: 'recipes', columns: ['name', 'class_name']);
            $existingRecipeClassNames = array_map(
                static fn($recipe) => $recipe->class_name,
                array_filter($existingRecipes ?: [], fn($recipe) => !$this->shouldExcludeByNameOrClassName($recipe->name, $recipe->class_name))
            );
            $recipesToDelete = array_diff($existingRecipeClassNames, $processedRecipeClassNames);

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

    private function assertDocsEntry(mixed $data): void {
        if (!is_array($data) || !isset($data['NativeClass']) || !isset($data['Classes']) || !is_array($data['Classes'])) {
            throw new InvalidArgumentException('Invalid Docs data structure');
        }
    }

    private function shouldExcludeDocsClassData(array $classData): bool {
        return $this->shouldExcludeByNameOrClassName(
            $classData['mDisplayName'] ?? '',
            $classData['ClassName'] ?? ''
        );
    }

    private function shouldExcludeByNameOrClassName(?string $name, ?string $className): bool {
        $name = trim((string) $name);
        $className = trim((string) $className);

        if (preg_match('/^FICSMAS Data Cartridge Day \d+$/i', $name)) {
            return true;
        }

        return (bool) preg_match('/FICSMAS.*Data.*Cartridge.*Day.*\d+|Data.*Cartridge.*Day.*\d+.*FICSMAS/i', $className);
    }

    private function getItemChanges(object $existing, Item $item): array {
        return $this->buildChanges([
            'name' => [$existing->name, $item->name],
            'form' => [$existing->form, $item->form],
            'class_name' => [$existing->class_name, $item->class_name],
        ]);
    }

    private function getBuildingChanges(object $existing, Building $building): array {
        return $this->buildChanges([
            'name' => [$existing->name, $building->name],
            'class_name' => [$existing->class_name, $building->class_name],
            'power_used' => [(float) $existing->power_used, (float) $building->power_used],
            'power_generation' => [(float) $existing->power_generation, (float) $building->power_produced],
        ]);
    }

    private function getRecipeChanges(object $existing, Recipe $recipe): array {
        $newValues = $this->getRecipeComparableValues($recipe);
        $existingValues = $this->getExistingRecipeComparableValues($existing);

        return $this->buildChanges([
            'name' => [$existingValues['name'], $newValues['name']],
            'class_name' => [$existingValues['class_name'], $newValues['class_name']],
            'building' => [$existingValues['building'], $newValues['building']],
            'product' => [$existingValues['product'], $newValues['product']],
            'second_product' => [$existingValues['second_product'], $newValues['second_product']],
            'export_amount_per_min' => [$existingValues['export_amount_per_min'], $newValues['export_amount_per_min']],
            'export_amount_per_min2' => [$existingValues['export_amount_per_min2'], $newValues['export_amount_per_min2']],
            'ingredients' => [$existingValues['ingredients'], $newValues['ingredients']],
        ]);
    }

    private function getRecipeComparableValues(Recipe $recipe): array {
        return [
            'name' => $recipe->name,
            'class_name' => $recipe->class_name,
            'building' => $recipe->buildingId->class_name,
            'product' => $recipe->itemId->class_name,
            'second_product' => $recipe->secondItemId?->class_name,
            'export_amount_per_min' => $this->normalizeFloat($recipe->exportAmountPerMin),
            'export_amount_per_min2' => $this->normalizeNullableFloat($recipe->secondExportAmountPerMin),
            'ingredients' => $this->normalizeIngredientList($this->recipeIngredientsToComparableArray($recipe->ingredients)),
        ];
    }

    private function getExistingRecipeComparableValues(object $existing): array {
        $building = $this->database->get(table: 'buildings', columns: ['class_name'], where: ['id' => $existing->buildings_id]);
        $item = $this->database->get(table: 'items', columns: ['class_name'], where: ['id' => $existing->item_id]);
        $secondItem = $existing->item_id2
            ? $this->database->get(table: 'items', columns: ['class_name'], where: ['id' => $existing->item_id2])
            : false;

        return [
            'name' => $existing->name,
            'class_name' => $existing->class_name,
            'building' => $building ? $building->class_name : null,
            'product' => $item ? $item->class_name : null,
            'second_product' => $secondItem ? $secondItem->class_name : null,
            'export_amount_per_min' => $this->normalizeFloat($existing->export_amount_per_min),
            'export_amount_per_min2' => $this->normalizeNullableFloat($existing->export_amount_per_min2),
            'ingredients' => $this->normalizeIngredientList($this->getExistingRecipeIngredients($existing->id)),
        ];
    }

    private function getExistingRecipeIngredients(int $recipeId): array {
        $rows = $this->database->query(
            'SELECT items.class_name, recipe_ingredients.import_amount_per_min
             FROM recipe_ingredients
             INNER JOIN items ON items.id = recipe_ingredients.items_id
             WHERE recipe_ingredients.recipes_id = ?',
            [$recipeId]
        );

        return array_map(static fn($row) => [
            'class_name' => $row->class_name,
            'amount' => (float) $row->import_amount_per_min,
        ], $rows ?: []);
    }

    private function recipeIngredientsToComparableArray(array $ingredients): array {
        return array_map(static fn(RecipeIngredient $ingredient) => [
            'class_name' => $ingredient->itemClass,
            'amount' => (float) $ingredient->importAmountPerMin,
        ], $ingredients);
    }

    private function normalizeIngredientList(array $ingredients): string {
        usort($ingredients, static fn($left, $right) => $left['class_name'] <=> $right['class_name']);

        return implode(', ', array_map(
            fn($ingredient) => $ingredient['class_name'] . ': ' . $this->formatComparableValue($this->normalizeFloat($ingredient['amount'])),
            $ingredients
        ));
    }

    private function buildChanges(array $fields): array {
        $changes = [];

        foreach ($fields as $field => [$oldValue, $newValue]) {
            $oldComparable = $this->normalizeComparableValue($oldValue);
            $newComparable = $this->normalizeComparableValue($newValue);

            if ($oldComparable === $newComparable) {
                continue;
            }

            $changes[] = [
                'field' => $field,
                'old' => $this->formatComparableValue($oldValue),
                'new' => $this->formatComparableValue($newValue),
            ];
        }

        return $changes;
    }

    private function normalizeComparableValue(mixed $value): string {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_float($value) || is_int($value) || is_numeric($value)) {
            return (string) $this->normalizeFloat($value);
        }

        return (string) $value;
    }

    private function normalizeFloat(mixed $value): float {
        return round((float) $value, 6);
    }

    private function normalizeNullableFloat(mixed $value): ?float {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->normalizeFloat($value);
    }

    private function formatComparableValue(mixed $value): string {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_float($value) || is_int($value) || is_numeric($value)) {
            return rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.');
        }

        return (string) $value;
    }

}
