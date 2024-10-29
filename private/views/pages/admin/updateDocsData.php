<?php
function extractNativeClass($nativeClass) {
    $item_name = explode('.', $nativeClass)[2];
    $item_name = str_replace("'", '', $item_name);
    return $item_name;
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

$json = json_decode($jsonData, true);


$defaultItemsNativeClasses = ['FGItemDescriptor', 'FGItemDescriptorBiomass', 'FGItemDescriptorNuclearFuel',
    'FGResourceDescriptor', 'FGAmmoTypeSpreadshot', 'FGAmmoTypeProjectile', 'FGAmmoTypeInstantHit', 'FGPowerShardDescriptor', 'FGItemDescriptorPowerBoosterFuel'];
$defaultBuildingNativeClasses = ['FGBuildableResourceExtractor', 'FGBuildableManufacturer', 'FGBuildableManufacturerVariablePower',
    'FGBuildableGeneratorNuclear', 'FGBuildableGeneratorFuel', 'FGBuildableWaterPump',
    'FGBuildablePortal', 'FGBuildablePortalSatellite', 'FGBuildablePowerBooster', 'FGBuildablePipelinePump'];

$existingClasses = [];
$existingBuildingClasses = [];

foreach ($json as $item) {
    $nativeClass = extractNativeClass($item['NativeClass']);

    // if 'buildable' in native class
    if (strpos($nativeClass, 'Build') !== false) {
        $existingBuildingClasses[] = $nativeClass;
    } else {
        $existingClasses[] = $nativeClass;
    }
}
?>

<div class="container">
    <h1 class="text-center mb-3">Update Docs Data</h1>
    <a href="/admin" class="btn btn-primary w-100 mb-3">Return to admin page</a>

    <div class="container">
        <form id="updateDocsDataForm">
            <div class="form-group">
                <label for="itemsNativeClasses">Items Native Classes</label>
                <select class="form-control" name="ItemsNativeClasses[]" multiple>
                    <option value="default" disabled>Select items classes</option>
                    <?php
                    foreach ($existingClasses as $class) {
                        $selected = in_array($class, $defaultItemsNativeClasses) ? 'selected' : '';
                        echo "<option value='$class' $selected>$class</option>";
                    }
                    ?>
                </select>
                <label for="buildingNativeClasses">Building Native Classes</label>
                <select class="form-control" name="BuildingNativeClasses[]" multiple>
                    <option value="default" disabled>Select building classes</option>
                    <?php
                    foreach ($existingBuildingClasses as $class) {
                        $selected = in_array($class, $defaultBuildingNativeClasses) ? 'selected' : '';
                        echo "<option value='$class' $selected>$class</option>";
                    }
                    ?>
                </select>
            </div>
        </form>
        <button id="updateDocsData" class="btn btn-primary w-100 my-3">
            Update Docs Data
        </button>

        <div id="updateDocsDataResponse"></div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        $('#updateDocsData').on('click', async function () {
            $('#updateDocsData').prop('disabled', true);
            $('#updateDocsData').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating Docs Data');
            $('#updateDocsDataResponse').after('');

            await sendAjaxRequest();
        });
    });

    async function sendAjaxRequest() {
        let jsonData = JSON.stringify(<?= $jsonData ?>);
        let ItemsNativeClasses = $('select[name="ItemsNativeClasses[]"]').val();
        let BuildingNativeClasses = $('select[name="BuildingNativeClasses[]"]').val();

        $.ajax({
            url: '/updateDocsData',
            type: 'POST',
            data: {
                jsonData: jsonData,
                ItemsNativeClasses: ItemsNativeClasses,
                BuildingNativeClasses: BuildingNativeClasses
            },
            success: function (response) {
                $('#updateDocsDataResponse').after(response['html']);
                $('#updateDocsData').prop('disabled', false);
                $('#updateDocsData').text('Update Docs Data');
            },
            error: function (response) {
                console.log(response);
            }
        });
    }
</script>
