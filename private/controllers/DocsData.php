<?php

class DocsData
{
    public array $recipes = [];
    public array $recipeIngredients = [];
    public array $buildings = [];
    public array $items = [];

    private mixed $jsonData;
    private array $itemsNativeClasses;
    private array $BuildingNativeClasses;
    private array $buildingClasses;
    private int $itemI = 1;
    private int $buildingI = 1;
    private int $recipeI = 1;
    private int $recipeIngredientI = 1;

    // Constructor
    function __construct(mixed $jsonData, array $itemsNativeClasses, array $BuildingNativeClasses)
    {
        if (empty($jsonData)) {
            throw new Exception('Json data is empty');
        }

        $this->jsonData = $jsonData;
        $this->itemsNativeClasses = $itemsNativeClasses;
        $this->BuildingNativeClasses = $BuildingNativeClasses;

        $this->setItems();
        $this->setBuildings();
        $this->setRecipes();
    }

    /**
     * Item Functions
     */

    private function setItems()
    {
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
    private function extractNativeClass($nativeClass)
    {
        $item_name = explode('.', $nativeClass)[2];
        $item_name = str_replace("'", '', $item_name);
        return $item_name;
    }

    private function isItemClass($nativeClass): bool
    {
        return in_array($nativeClass, $this->itemsNativeClasses);
    }

    private function addItems(array $classes): void
    {
        foreach ($classes as $itemData) {
            $this->items[] = $this->createItem($itemData);
        }
    }


    /**
     * Building Functions
     */

    private function createItem(array $itemData): Item
    {
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
    private function setBuildings()
    {
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

    private function isBuildingClass($nativeClass): bool
    {
        return in_array($nativeClass, $this->BuildingNativeClasses);
    }

    private function getPowerConsumption(array $buildingData): int
    {
        return $buildingData['mEstimatedMaximumPowerConsumption'] ??
            $buildingData['mPowerConsumption'] ??
            0;
    }

    private function getPowerProduction(array $buildingData): int
    {
        return $buildingData['mBasePowerProduction'] ??
            $buildingData['mPowerProduction'] ??
            0;
    }

    private function addBuilding(array $buildingData, int $powerConsumption, int $powerProduction): void
    {
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

    private function setRecipes()
    {
        foreach ($this->jsonData as $data) {
            $nativeClass = $this->extractNativeClass($data['NativeClass']);

            if ($nativeClass === 'FGRecipe') {
                $this->processRecipes($data['Classes']);
            }
        }
    }

    private function processRecipes(array $recipesData): void
    {
        foreach ($recipesData as $recipeData) {
            $producedIn = $this->extractBuildNames($recipeData['mProducedIn']);

            foreach ($producedIn as $buildingClass) {
                if (in_array($buildingClass, $this->buildingClasses)) {
                    $this->createRecipe($recipeData, $buildingClass);
                }
            }
        }
    }

    private function extractBuildNames($producedIn): array
    {
        preg_match_all('/Build_[^\/]+_C/', $producedIn, $matches);
        return array_map(function ($building) {
            return explode('.', $building)[1]; // Get the part after '.'
        }, $matches[0]);
    }

    private function createRecipe(array $recipeData, string $buildingClass): void
    {
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

        $this->recipes[] = new recipe(
            $this->recipeI,
            $recipeData['mDisplayName'],
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

    private function extractProductsAndAmounts($products, $items): array
    {
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

    private function createRecipeIngredients(array $recipeIngredients, float $duration): array
    {
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

    private function adjustProducedItems(array &$producedItems): void
    {
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
     * General Functions
     */

    private function calculateExportPerMin(array $producedItems, float $duration, int $index): ?float
    {
        return isset($producedItems[$index]) ? $producedItems[$index][1] / $duration : null;
    }


}