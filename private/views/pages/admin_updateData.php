<?php
// Include the required classes (assuming autoloading is set up or you have included the files)
require_once 'classes/item.php';
require_once 'classes/building.php';
require_once 'classes/recipe.php';
require_once 'classes/recipeIngredient.php';

// Function to extract building names from a given path
function extract_build_names($path)
{
    preg_match_all('/Build_[^\/]+_C/', $path, $matches);
    return array_map(function ($building) {
        if (!empty($building)) {
            return pathinfo($building, PATHINFO_FILENAME);
        }
    }, $matches[0]);
}

// Load JSON file
$json_data = file_get_contents('Docs.json', true);
// Decode JSON data utf16 to utf8
$json_data = mb_convert_encoding($json_data, 'UTF-8', 'UTF-16');
$data = json_decode($json_data, true);

$ItemsNativeClasses = ['FGItemDescriptor', 'FGItemDescriptorBiomass', 'FGItemDescriptorNuclearFuel',
    'FGResourceDescriptor', 'FGAmmoTypeSpreadshot', 'FGAmmoTypeProjectile', 'FGAmmoTypeInstantHit'];
$BuildingNativeClasses = ['FGBuildableResourceExtractor', 'FGBuildableManufacturer',
    'FGBuildableManufacturerVariablePower', 'FGBuildableGeneratorNuclear',
    'FGBuildableGeneratorFuel', 'FGBuildableWaterPump', 'FGBuildablePortal', 'FGBuildablePortalSatellite', 'FGBuildablePowerBooster'];

$buildings = [];
$building_classes = [];
$items = [];
$recipes = [];

$item_i = 1;
$building_i = 1;
$recipe_i = 1;
$recipe_ing_i = 1;

foreach ($data as $native_class) {
    $item_name = basename($native_class['NativeClass']);
    // remove FactoryGame. prefix
    $item_name = substr($item_name, 12);
    $item_name = substr($item_name, 0, -1);
    if (in_array($item_name, $ItemsNativeClasses)) {
        foreach ($native_class['Classes'] as $data_item) {
            $items[] = new item(
                $item_i++,
                $data_item['mDisplayName'],
                $data_item['mForm'],
                $data_item['ClassName']
            );
        }
    }

    if (in_array($item_name, $BuildingNativeClasses)) {
        foreach ($native_class['Classes'] as $data_building) {
            $power_consumption = !empty($data_building['mEstimatedMaximumPowerConsumption']) ? $data_building['mEstimatedMaximumPowerConsumption'] : $data_building['mPowerConsumption'];
            $power_production = !empty($data_building['mBasePowerProduction']) ? $data_building['mBasePowerProduction'] : (!empty($data_building['mPowerProduction']) ? $data_building['mPowerProduction'] : 0);

            $buildings[] = new building(
                $building_i++,
                $data_building['mDisplayName'],
                $data_building['ClassName'],
                $power_consumption,
                $power_production
            );
            $data_building['ClassName'] = substr($data_building['ClassName'], 0, -2);
            $building_classes[] = $data_building['ClassName'];
        }
    }
}
function extract_products_and_amounts($s)
{
    preg_match_all('/Desc_[^\/]+_C/', $s, $product_matches);
    preg_match_all('/Amount=(\d+)/', $s, $amount_matches);

    $amounts = array_map('intval', $amount_matches[1]);
    $products = [];

    foreach ($product_matches[0] as $product) {
        $product_name = basename($product);
        foreach ($GLOBALS['items'] as $item_data) {
            if ($item_data->class_name === $product_name) {
                $products[] = $item_data;
                break;
            }
        }
    }
    return array_map(null, $products, $amounts);
}

var_dump($building_classes);

foreach ($data as $native_class) {
    $item_name = basename($native_class['NativeClass']);
    if (strpos($item_name, 'FGRecipe') !== false) {
        foreach ($native_class['Classes'] as $data_item) {
            $produced_in = extract_build_names($data_item['mProducedIn']);
            foreach ($produced_in as $building_class) {
                var_dump($building_class);
                if (in_array($building_class, $building_classes)) {
                    $duration = floatval($data_item['mManufactoringDuration']) / 60;
                    $produced_items = extract_products_and_amounts($data_item['mProduct']);
                    $recipe_ingredients = extract_products_and_amounts($data_item['mIngredients']);
                    $recipe_ingredients_list = [];

                    if (empty($produced_items)) {
                        continue;
                    }

                    foreach ($recipe_ingredients as $ingredient) {
                        if ($ingredient[0]->form === 'RF_LIQUID' || $ingredient[0]->form === 'RF_GAS') {
                            $ingredient[1] /= 1000;
                        }

                        $recipe_ingredients_list[] = new recipeIngredient(
                            $recipe_ing_i++,
                            $recipe_i,
                            $ingredient[0]->class_name,
                            $ingredient[0]->id,
                            $ingredient[1] / $duration
                        );
                    }

                    if ($produced_items[0][0]->form === 'RF_LIQUID' || $produced_items[0][0]->form === 'RF_GAS') {
                        $produced_items[0][1] /= 1000;
                    }

                    if (count($produced_items) > 1) {
                        if ($produced_items[1][0]->form === 'RF_LIQUID' || $produced_items[1][0]->form === 'RF_GAS') {
                            $produced_items[1][1] /= 1000;
                        }
                    }

                    $export_per_min = !empty($produced_items) ? $produced_items[0][1] / $duration : null;
                    $second_export_per_min = count($produced_items) > 1 ? $produced_items[1][1] / $duration : null;

                    $recipes[] = new recipe(
                        $recipe_i++,
                        $data_item['mDisplayName'],
                        $data_item['ClassName'],
                        $buildings[array_search($building_class, $building_classes)],
                        !empty($produced_items) ? $produced_items[0][0] : null,
                        count($produced_items) > 1 ? $produced_items[1][0]->id : null,
                        $export_per_min,
                        $second_export_per_min,
                        $recipe_ingredients_list
                    );
                }
            }
        }
    }
}

// Connect to the database
$connection = new mysqli('localhost', 'timmy', '9aRF8d6oZJSl6yX', 'satisfactoryplanner');

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

$processed_item_class_names = [];
$processed_building_class_names = [];
$processed_recipe_class_names = [];

// --- Process Items ---
echo "Processing items<br>";
foreach ($items as $index => $item_data) {
    $processed_item_class_names[] = $item_data->class_name;

    $stmt = $connection->prepare("SELECT id FROM items WHERE class_name = ?");
    $stmt->bind_param("s", $item_data->class_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $items[$index]->id = $result->fetch_assoc()['id'];
    } else {
        $stmt = $connection->prepare("INSERT INTO items (name, form, class_name) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $item_data->name, $item_data->form, $item_data->class_name);
        $stmt->execute();
        $items[$index]->id = $connection->insert_id;
    }
}

// Delete items that were not processed
$existing_item_class_names = $connection->query("SELECT class_name FROM items")->fetch_all(MYSQLI_ASSOC);
$existing_item_class_names = array_column($existing_item_class_names, 'class_name');
$items_to_delete = array_diff($existing_item_class_names, $processed_item_class_names);

//foreach ($items_to_delete as $class_name) {
//    $stmt = $connection->prepare("DELETE FROM items WHERE class_name = ?");
//    $stmt->bind_param("s", $class_name);
//    $stmt->execute();
//}

$connection->commit();

// --- Process Buildings ---
echo "Processing buildings<br>";
foreach ($buildings as $index => $building_data) {
    $processed_building_class_names[] = $building_data->class_name;

    $stmt = $connection->prepare("SELECT id FROM buildings WHERE class_name = ?");
    $stmt->bind_param("s", $building_data->class_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $buildings[$index]->id = $result->fetch_assoc()['id'];
    } else {
        $stmt = $connection->prepare("INSERT INTO buildings (name, class_name, power_used, power_generation) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssdd", $building_data->name, $building_data->class_name, $building_data->power_used, $building_data->power_produced);
        $stmt->execute();
        $buildings[$index]->id = $connection->insert_id;
    }
}

// Delete buildings that were not processed
$existing_building_class_names = $connection->query("SELECT class_name FROM buildings")->fetch_all(MYSQLI_ASSOC);
$existing_building_class_names = array_column($existing_building_class_names, 'class_name');
$buildings_to_delete = array_diff($existing_building_class_names, $processed_building_class_names);

//foreach ($buildings_to_delete as $class_name) {
//    $stmt = $connection->prepare("DELETE FROM buildings WHERE class_name = ?");
//    $stmt->bind_param("s", $class_name);
//    $stmt->execute();
//}

$connection->commit();

// --- Process Recipes ---
echo "Processing recipes<br>";
var_dump($recipes);
foreach ($recipes as $index => $recipe_data) {
    $processed_recipe_class_names[] = $recipe_data->class_name;

    $stmt = $connection->prepare("SELECT id FROM recipes WHERE class_name = ?");
    $stmt->bind_param("s", $recipe_data->class_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $recipes[$index]->id = $result->fetch_assoc()['id'];
    } else {
        $stmt = $connection->prepare("INSERT INTO recipes (name, class_name, produced_amount_per_min, produced_amount_per_min2, building_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdds", $recipe_data->name, $recipe_data->class_name, $recipe_data->export_amount_per_min, $recipe_data->export_amount_per_min2, $recipe_data->building_id);
        $stmt->execute();
        $recipes[$index]->id = $connection->insert_id;
    }

    // Process recipe ingredients
    foreach ($recipe_data->ingredients as $ingredient) {
        $stmt = $connection->prepare("INSERT INTO recipe_ingredients (recipe_id, item_id, amount) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $recipe_data->id, $ingredient->item_id, $ingredient->amount);
        $stmt->execute();
    }
}

// Delete recipes that were not processed
$existing_recipe_class_names = $connection->query("SELECT class_name FROM recipes")->fetch_all(MYSQLI_ASSOC);
$existing_recipe_class_names = array_column($existing_recipe_class_names, 'class_name');
$recipes_to_delete = array_diff($existing_recipe_class_names, $processed_recipe_class_names);

//foreach ($recipes_to_delete as $class_name) {
//    $stmt = $connection->prepare("DELETE FROM recipes WHERE class_name = ?");
//    $stmt->bind_param("s", $class_name);
//    $stmt->execute();
//}

$connection->commit();
$connection->close();

echo "Data processing complete.<br>";
