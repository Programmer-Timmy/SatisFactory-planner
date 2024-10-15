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
    <?php if (!empty($added_stuff['items'] || $added_stuff['buildings'] || $added_stuff['recipes'])): ?>
        <div class='card mb-4'>
            <div class='card-header bg-info text-black '>Added</div>
            <?php foreach ($added_stuff as $typeName => $type) : ?>
            <?php if (empty($type)) continue; ?>
                <div class='card-body row'>
                    <div class='col-lg-12'>
                        <div class='card mb-3'>
                            <div class='card-header'><?= $typeName ?></div>
                            <div class='card-body row'>
                                <?php foreach ($type as $stuff) : ?>
                                    <div class="col-lg-4">
                                        <div class="card mb-3">
                                            <div class="card-header"><?= $stuff['name'] ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($deleted_stuff['items'] || $deleted_stuff['buildings'] || $deleted_stuff['recipes'])): ?>
        <div class='card mb-4'>
            <div class='card-header bg-danger text-black '>Deleted</div>
            <?php foreach ($deleted_stuff as $typeName => $type) : ?>
            <?php if (empty($type)) continue; ?>
                <div class='card-body row'>
                    <div class='col-lg-12'>
                        <div class='card mb-3'>
                            <div class='card-header'><?= $typeName ?></div>
                            <div class='card-body row'>
                                <?php foreach ($type as $stuff) : ?>
                                    <div class="col-lg-4">
                                        <div class="card mb-3">
                                            <div class="card-header"><?= $stuff['name'] ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($updated_stuff['items'] || $updated_stuff['buildings'] || $updated_stuff['recipes'])): ?>
        <div class='card mb-4'>
            <div class='card-header bg-warning text-black '>Updated</div>
            <?php foreach ($updated_stuff as $typeName => $type) : ?>
            <?php if (empty($type)) continue; ?>
                <div class='card-body row'>
                    <div class='col-lg-12'>
                        <div class='card mb-3'>
                            <div class='card-header'><?= $typeName ?></div>
                            <div class='card-body row'>
                                <?php foreach ($type as $stuff) : ?>
                                    <div class="col-lg-4">
                                        <div class="card mb-3">
                                            <div class="card-header"><?= $stuff['name'] ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <a href="/admin" class="btn btn-primary w-100">Return to admin page</a>
</div>

