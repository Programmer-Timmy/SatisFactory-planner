<!--return to admin page button-->
<div class="container">
    <a href="/admin" class="btn btn-primary w-100 mb-3">Return to admin page</a>

    <?php

    function extract_build_names($path) {
        // Use regex to find building names
        preg_match_all('/Build_[^\/]+_C/', $path, $matches);
        return array_map(function ($building) {
            return explode('.', $building)[1]; // Get the part after '.'
        }, $matches[0]);
    }

    $filePath = __DIR__ . '/../../../../static/Docs.json';

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

    $docsData = new DocsData($data, $ItemsNativeClasses, $BuildingNativeClasses);

    $docsData->insertItems();
    $docsData->insertBuildings();
    $docsData->insertRecipes();

    $added_stuff = $docsData->added_stuff;
    $deleted_stuff = $docsData->deleted_stuff;
    $updated_stuff = $docsData->updated_stuff;
    ?>
    <?php if (!empty($added_stuff)): ?>
        <div class='card mb-4'>
            <div class='card-header bg-info '>Added</div>
            <div class='card-body'>
                <ul class='list-group'>
                    <?php foreach ($added_stuff as $stuff) : ?>
                        <li class='list-group-item d-flex justify-content-between align-items-center'>
                            <span class='badge badge-primary badge-pill text-black'><?= $stuff['name'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!empty($deleted_stuff)): ?>
        <div class='card mb-4'>
            <div class='card-header bg-dange'>Deleted</div>
            <div class='card-body'>
                <ul class='list-group'>
                    <?php foreach ($deleted_stuff as $stuff) : ?>
                        <li class='list-group item d-flex justify-content-between align-items-center'>
                            <span class='badge badge-primary badge-pill text-black'><?= $stuff['name'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!empty($updated_stuff)): ?>
        <div class='card mb-4'>
            <div class='card-header bg-warning '>Updated</div>
            <div class='card-body'>
                <ul class='list-group'>
                    <?php foreach ($updated_stuff as $stuff) : ?>
                        <li class='list-group item d-flex justify-content-between align-items-center'>
                            <span class='badge badge-primary badge-pill text-black'><?= $stuff['name'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>
    <a href="/admin" class="btn btn-primary w-100">Return to admin page</a>
</div>

