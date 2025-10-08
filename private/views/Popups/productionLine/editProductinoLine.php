<?php
global $productLine;
if ($_POST && isset($_POST['productionLineName'])) {
    // Assuming you've included or defined the Database class somewhere
    $productionLineName = $_POST['productionLineName'];

    // Validate Production Line Name Length
    if (strlen($productionLineName) > 45) {
        $_SESSION['error'] = 'Production Line Name is too lengthy. Please use up to 45 characters.';
    } elseif ($productionLineName !== strip_tags($productionLineName)) {
        $_SESSION['error'] = 'Security Alert: Unauthorized characters detected in Production Line Name. Nice try, but FICSIT Security has blocked that!';
    }

    if (!isset($_SESSION['error']) || !$_SESSION['error']) {
        $productLineId = $productLine->id;
        $active = isset($_POST['productionLineActive']) ? 1 : 0;

        $productionLineName = htmlspecialchars($productionLineName);

        ProductionLines::updateProductionLine($productLineId, $productionLineName, $active);

        header("Location: /game_save/$productLine->game_saves_id/production_line/$productLineId");
        exit();
    }
}

$productionLineSettings = ProductionLineSettings::getProductionLineSettings(intval($_GET['id'] ?? null));
?>

<div class="modal fade" id="editProductionLine" tabindex="-1"
     aria-labelledby="popupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="popupModalLabel">Update production line</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="successAlert" class="alert alert-success d-none fade" role="alert">
                    Import was successful!
                </div>
                <div id="errorAlert" class="alert alert-danger d-none fade" role="alert"></div>
                <div class="row mb-3">
                    <h5>Settings</h5>
                    <div class="col-6 py-2">
                        <div class="d-flex align-items-center justify-content-between" data-bs-toggle="tooltip"
                             data-bs-placement="top" data-bs-html="true"
                             title="This will automatically calculate the import and export values for the production line.">
                            <label for="auto_import_export" class="form-label me-2">Auto Import-Export</label>
                            <input type="checkbox" id="auto_import_export" class="from-control" data-onstyle="success"
                                   data-offstyle="danger"
                                   data-toggle="toggle" <?= $productionLineSettings->auto_import_export ? 'checked' : '' ?>>
                        </div>
                    </div>
                    <div class="col-6 py-2">
                        <div class="d-flex align-items-center justify-content-between" data-bs-toggle="tooltip"
                             data-bs-placement="top" data-bs-html="true"
                             title="This will automatically calculate the power and machine values for the production line.">
                            <label for="auto_power_machine" class="form-label me-2">Auto Power-Machine</label>
                            <input type="checkbox" id="auto_power_machine" class="from-control" data-onstyle="success"
                                   data-offstyle="danger"
                                   data-toggle="toggle" <?= $productionLineSettings->auto_power_machine ? 'checked' : '' ?>>
                        </div>
                    </div>
                </div>
                <form method="post" id="editProductionLineForm" class="row">
                    <h5>Production Line</h5>
                    <div class="mb-3 col-10">
                        <label for="productionLineName" class="form-label">Production Line Name</label>
                        <input type="text"
                               value="<?= isset($productionLineName) ? htmlspecialchars($productionLineName) : $productLine->title ?>"
                               id="productionLineName" name="productionLineName" class="form-control" maxlength="45"
                               required>
                    </div>
                    <div class="mb-3 col-2">
                        <label for="productionLineActive" class="form-label">Active</label><br>
                        <input type="checkbox" id="productionLineActive" class="from-control" data-onstyle="success"
                               data-offstyle="danger" data-toggle="toggle" name="productionLineActive"
                            <?php if ($productLine->active) echo 'checked'; ?>>
                    </div>
                </form>
                <div class="row mb-3">
                    <h5>Import/Export</h5>
                    <div class="col-8 pe-0">
                        <input type="file" id="importFile" name="file" accept=".json"
                               class="form-control rounded-0 rounded-start" required>
                        <div class="invalid-feedback">Please select a valid JSON file.</div>
                    </div>
                    <div class="col-2 p-0">
                        <button class="btn btn-success w-100 rounded-0" id="importButton">Import</button>
                    </div>
                    <div class="col-2 ps-0">
                        <button class="btn btn-primary w-100 rounded-0 rounded-end" id="exportButton">Export</button>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <!-- Collapsible Button -->
                        <button class="btn btn-primary w-100" type="button" data-bs-toggle="collapse"
                                data-bs-target="#cachedData" aria-expanded="false" aria-controls="cachedData">
                            Cached Data
                        </button>
                        <!-- Collapsible Content -->
                        <div class="collapse" id="cachedData">
                            <div class="card card-body mt-3">
                                <div class="row">
                                    <div class="col-3">
                                        <h6>Recipes</h6>
                                        <p id="cachedRecipes" class="col-12">0</p>
                                    </div>
                                    <div class="col-3">
                                        <h6>Buildings</h6>
                                        <p id="cachedBuildings" class="col-12">0</p>
                                    </div>
                                    <div class="col-6">
                                        <h6>Remove Cache</h6>
                                        <button class="btn btn-danger w-100" id="removeCache">Remove Cache</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button type="submit" form="editProductionLineForm" class="btn btn-primary" data-bs-toggle="tooltip"
                        data-bs-placement="top" data-bs-html="true"
                        title="This will update the production line with the new settings. Only needed if you changed the name or active status.">
                    Update Production Line
                </button>
            </div>
        </div>
    </div>
</div>
<script>
    document.getElementById('edit_product_line').addEventListener('click', function () {
        const addProductionLine = new bootstrap.Modal(document.getElementById('editProductionLine'));
        addProductionLine.show();
    });
</script>
