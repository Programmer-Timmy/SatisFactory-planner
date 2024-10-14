<?php

require_once 'classes/item.php';
require_once 'classes/building.php';
require_once 'classes/recipe.php';
require_once 'classes/recipeIngredient.php';

function extract_build_names($path)
{
    // Use regex to find building names
    preg_match_all('/Build_[^\/]+_C/', $path, $matches);
    return array_map(function ($building) {
        return explode('.', $building)[1]; // Get the part after '.'
    }, $matches[0]);
}

$filePath = __DIR__ . '/Docs.json'; // Path to your JSON file

// Check if the file exists before attempting to read it
if (file_exists($filePath)) {
    // Read the file content
    $jsonData = file_get_contents($filePath, true);

    // Convert UTF-16 to UTF-8 if necessary
    if (mb_detect_encoding($jsonData, 'UTF-16', true)) {
        $jsonData = mb_convert_encoding($jsonData, 'UTF-8', 'UTF-16');
    }

    // Now you can use $jsonData for further processing
} else {
    // Handle the case where the file does not exist
    echo "File not found: " . $filePath;
    exit(1);
}

$data = json_decode($jsonData, true);

$ItemsNativeClasses = ['FGItemDescriptor', 'FGItemDescriptorBiomass', 'FGItemDescriptorNuclearFuel',
    'FGResourceDescriptor', 'FGAmmoTypeSpreadshot', 'FGAmmoTypeProjectile', 'FGAmmoTypeInstantHit', 'FGPowerShardDescriptor', 'FGItemDescriptorPowerBoosterFuel'];
$BuildingNativeClasses = ['FGBuildableResourceExtractor', 'FGBuildableManufacturer', 'FGBuildableManufacturerVariablePower',
    'FGBuildableGeneratorNuclear', 'FGBuildableGeneratorFuel', 'FGBuildableWaterPump',
    'FGBuildablePortal', 'FGBuildablePortalSatellite', 'FGBuildablePowerBooster'];

$buildings = [];
$building_classes = [];
$items = [];
$recipes = [];

$item_i = 1;
$building_i = 1;
$recipe_i = 1;
$recipe_ing_i = 1;

foreach ($data as $native_class) {
    $item_name = explode('.', $native_class['NativeClass'])[2];
    $item_name = str_replace("'", '', $item_name);
    if (in_array($item_name, $ItemsNativeClasses)) {
        foreach ($native_class['Classes'] as $data_item) {
            $items[] = new item($item_i, $data_item['mDisplayName'], $data_item['mForm'], $data_item['ClassName']);
            $item_i++;
        }
    }

    if (in_array($item_name, $BuildingNativeClasses)) {
        foreach ($native_class['Classes'] as $data_building) {
            $power_consumption = isset($data_building['mEstimatedMaximumPowerConsumption']) && $data_building['mEstimatedMaximumPowerConsumption'] != 0
                ? $data_building['mEstimatedMaximumPowerConsumption']
                : (isset($data_building['mPowerConsumption']) ? $data_building['mPowerConsumption'] : 0);

            $power_production = isset($data_building['mBasePowerProduction']) && $data_building['mBasePowerProduction'] != 0
                ? $data_building['mBasePowerProduction']
                : (isset($data_building['mPowerProduction']) ? $data_building['mPowerProduction'] : 0);

            $buildings[] = new building($building_i, $data_building['mDisplayName'], $data_building['ClassName'], $power_consumption, $power_production);
            $building_classes[] = $data_building['ClassName'];
            $building_i++;
        }
    }
}

# added list
$added_stuff = [];

function extract_products_and_amounts($s, $items)
{
    preg_match_all('/Desc_[^\/]+_C/', $s, $product_matches);
    preg_match_all('/Amount=(\d+)/', $s, $amount_matches);

//    if rocket fuel is i p
    $amounts = array_map('intval', $amount_matches[1]);

    $products = [];
    foreach ($product_matches[0] as $product) {
        $product_name = explode('.', $product)[1];
        foreach ($items as $item_data) {
            if ($item_data->class_name === $product_name) {
                $products[] = $item_data;
                break;
            }
        }
    }

    return array_map(null, $products, $amounts);
}

foreach ($data as $native_class) {
    $item_name = explode('.', $native_class['NativeClass'])[2];
    $item_name = str_replace("'", '', $item_name);
    if ($item_name === 'FGRecipe') {
        foreach ($native_class['Classes'] as $data_item) {
            $produced_in = extract_build_names($data_item['mProducedIn']);
            foreach ($produced_in as $building_class) {
                if (in_array($building_class, $building_classes)) {
                    $duration = floatval($data_item['mManufactoringDuration']) / 60;
                    $produced_items = extract_products_and_amounts($data_item['mProduct'], $items);
                    $recipe_ingredients = extract_products_and_amounts($data_item['mIngredients'], $items);
                    $recipe_ingredients_list = [];

                    if (count($produced_items) <= 0) continue;
                    if ($produced_items[0][0] == null) continue;

                    foreach ($recipe_ingredients as $ingredient) {

                        if ($ingredient[0]->form == 'RF_LIQUID' || $ingredient[0]->form == 'RF_GAS') {
                            $ingredient[1] /= 1000;
                        }

                        $recipe_ingredients_list[] = new recipeIngredient($recipe_ing_i, $recipe_i, $ingredient[0]->class_name, $ingredient[0]->id, $ingredient[1] / $duration);
                        $recipe_ing_i++;
                    }

                    if ($produced_items[0][0]->form == 'RF_LIQUID' || $produced_items[0][0]->form == 'RF_GAS') {
                        $produced_items[0][1] /= 1000;
                    }
//                   # debug of the produced item form below

                    if (count($produced_items) > 1 && ($produced_items[1][0]->form == 'RF_LIQUID' || $produced_items[1][0]->form == 'RF_GAS')) {
                        $produced_items[1][1] /= 1000;
                    }

                    $export_per_min = count($produced_items) > 0 ? $produced_items[0][1] / $duration : null;
                    $second_export_per_min = count($produced_items) > 1 ? $produced_items[1][1] / $duration : null;
                    $recipes[] = new recipe($recipe_i, $data_item['mDisplayName'], $data_item['ClassName'], $buildings[array_search($building_class, $building_classes)], $produced_items[0][0], $produced_items[1][0] ?? null, $export_per_min, $second_export_per_min, $recipe_ingredients_list);
                    $recipe_i++;
                }
            }
        }
    }
}
$pdo = new PDO('mysql:host=localhost;dbname=satisfactory_planner', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->beginTransaction();

echo "<div class='alert alert-info' role='alert'>connected to database</div>";
try {
    $sql_commands = [];
    // Track processed class_names for items, buildings, and recipes
    $processed_item_class_names = [];
    $processed_building_class_names = [];
    $processed_recipe_class_names = [];

    // --- Process Items ---
    echo "<div class='alert alert-info' role='alert'>Processing items</div>";
    foreach ($items as $index => $item_data) {
        $processed_item_class_names[] = $item_data->class_name;

        $stmt = $pdo->prepare("SELECT id FROM items WHERE class_name = ?");
        $stmt->execute([$item_data->class_name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $items[$index]->id = $result['id'];
            $stmt = $pdo->prepare("UPDATE items SET name = ?, form = ?, class_name = ? WHERE id = ?");
            $stmt->execute([$item_data->name, $item_data->form, $item_data->class_name, $item_data->id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO items (name, form, class_name) VALUES (?, ?, ?)");
            $stmt->execute([$item_data->name, $item_data->form, $item_data->class_name]);
            $items[$index]->id = $pdo->lastInsertId();
            $added_stuff[] = ['type' => 'item', 'name' => $item_data->name];
        }
    }

    // Delete unprocessed items
    $stmt = $pdo->query("SELECT class_name FROM items");
    $existing_item_class_names = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $items_to_delete = array_diff($existing_item_class_names, $processed_item_class_names);
    foreach ($items_to_delete as $class_name) {
        $stmt = $pdo->prepare("DELETE FROM items WHERE class_name = ?");
        $stmt->execute([$class_name]);
    }

    // --- Process Buildings ---
    echo "<div class='alert alert-info' role='alert'>Processing buildings</div>";
    foreach ($buildings as $index => $building_data) {
        $processed_building_class_names[] = $building_data->class_name;

        $stmt = $pdo->prepare("SELECT id FROM buildings WHERE class_name = ?");
        $stmt->execute([$building_data->class_name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $buildings[$index]->id = $result['id'];
            $stmt = $pdo->prepare("UPDATE buildings SET name = ?, class_name = ?, power_used = ?, power_generation = ? WHERE id = ?");
            $stmt->execute([$building_data->name, $building_data->class_name, $building_data->power_used, $building_data->power_produced, $building_data->id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO buildings (name, class_name, power_used, power_generation) VALUES (?, ?, ?, ?)");
            $stmt->execute([$building_data->name, $building_data->class_name, $building_data->power_used, $building_data->power_produced]);
            $buildings[$index]->id = $pdo->lastInsertId();
            $added_stuff[] = ['type' => 'building', 'name' => $building_data->name];
        }
    }

    // Delete unprocessed buildings
    $stmt = $pdo->query("SELECT class_name FROM buildings");
    $existing_building_class_names = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $buildings_to_delete = array_diff($existing_building_class_names, $processed_building_class_names);
    foreach ($buildings_to_delete as $class_name) {
        $stmt = $pdo->prepare("DELETE FROM buildings WHERE class_name = ?");
        $stmt->execute([$class_name]);
    }

    // --- Process Recipes ---
    echo "<div class='alert alert-info' role='alert'>Processing recipes</div>";
    foreach ($recipes as $index => $recipe_data) {
        $processed_recipe_class_names[] = $recipe_data->class_name;

        $stmt = $pdo->prepare("SELECT id FROM recipes WHERE class_name = ?");
        $stmt->execute([$recipe_data->class_name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $item_id = $pdo->query("SELECT id FROM items WHERE class_name = '{$recipe_data->itemId->class_name}'")->fetch(PDO::FETCH_ASSOC)['id'];
        if ($recipe_data->secondItemId) {
            $item_id2 = $pdo->query("SELECT id FROM items WHERE class_name = '{$recipe_data->secondItemId->class_name}'")->fetch(PDO::FETCH_ASSOC)['id'];
        } else {
            $item_id2 = null;
        }
        $building_id = $pdo->query("SELECT id FROM buildings WHERE class_name = '{$recipe_data->buildingId->class_name}'")->fetch(PDO::FETCH_ASSOC)['id'];

        if ($result) {
            $recipes[$index]->id = $result['id'];
            $stmt = $pdo->prepare("UPDATE recipes SET name = ?, class_name = ?, buildings_id = ?, export_amount_per_min = ?, export_amount_per_min2 = ?, item_id = ?, item_id2 = ? WHERE id = ?");
            $stmt->execute([$recipe_data->name, $recipe_data->class_name, $building_id, $recipe_data->exportAmountPerMin, $recipe_data->secondExportAmountPerMin, $item_id, $item_id2, $recipe_data->id]);

        } else {
            $stmt = $pdo->prepare("INSERT INTO recipes (name, class_name, buildings_id, export_amount_per_min, export_amount_per_min2, item_id, item_id2) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$recipe_data->name, $recipe_data->class_name, $building_id, $recipe_data->exportAmountPerMin, $recipe_data->secondExportAmountPerMin, $item_id, $item_id2]);
            $recipes[$index]->id = $pdo->lastInsertId();
            $added_stuff[] = ['type' => 'recipe', 'name' => $recipe_data->name];
        }


        // Handle recipe ingredients
        foreach ($recipe_data->ingredients as $ingredient) {
            $item_id = $pdo->query("SELECT id FROM items WHERE class_name = '{$ingredient->itemClass}'")->fetch(PDO::FETCH_ASSOC)['id'];

            $stmt = $pdo->prepare("INSERT INTO recipe_ingredients (recipes_id, items_id, import_amount_per_min) VALUES (?, ?, ?)");
            $stmt->execute([$recipe_data->id, $item_id, $ingredient->importAmountPerMin]);
        }
    }

    // Delete unprocessed recipes
    $stmt = $pdo->query("SELECT class_name FROM recipes");
    $existing_recipe_class_names = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $recipes_to_delete = array_diff($existing_recipe_class_names, $processed_recipe_class_names);
    foreach ($recipes_to_delete as $class_name) {
        $stmt = $pdo->prepare("DELETE FROM recipes WHERE class_name = ?");
        $stmt->execute([$class_name]);
    }

    echo "<div class='alert alert-success' role='alert'>Data updated successfully</div>";
    $pdo->commit();

} catch (PDOException $e) {
    echo "<div class='alert alert-danger' role='alert'>An error occurred: " . $e->getMessage() . "</div>";
    $pdo->rollBack();
}

echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'>Added Items, Buildings, and Recipes</div>";
echo "<div class='card-body'>";
echo "<ul class='list-group'>";

// Loop through the added items, buildings, and recipes
foreach ($added_stuff as $stuff) {
    echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
    echo "<span>{$stuff['type']}</span>";
    echo "<span class='badge badge-primary badge-pill'>{$stuff['name']}</span>";
    echo "</li>";
}

echo "</ul>";
echo "</div>"; // card-body
echo "</div>"; // card
