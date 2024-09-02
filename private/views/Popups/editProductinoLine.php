<?php
global $productLine;
if ($_POST && isset($_POST['productionLineName'])) {
    // Assuming you've included or defined the Database class somewhere
    $productionLineName = $_POST['productionLineName'];

    $productLineId = $productLine->id;
    $active = isset($_POST['productionLineActive']) ? 1 : 0;

    if (ProductionLines::updateProductionLine($productLineId, $productionLineName, $active)) {
        header('Location: /production_line?id=' . $productLineId);
        exit();
    }
}

$productionLineSettings = ProductionLineSettings::getProductionLineSettings(intval($_GET['id']), $_SESSION['userId']);
if (!$productionLineSettings) {
    $productionLineSettings = ProductionLineSettings::addProductionLineSettings(intval($_GET['id']), $_SESSION['userId']);
}
?>

<div class="modal fade" id="editProductionLine" tabindex="-1" aria-labelledby="popupModalLabel" aria-hidden="true">
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
                <form method="post" id="editProductionLineForm" class="row">
                    <div class="mb-3 col-10">
                        <label for="productionLineName" class="form-label">Production Line Name</label>
                        <input type="text" value="<?= $productLine->title ?>" class="form-control"
                               id="productionLineName" name="productionLineName"
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
                    <h5>Settings</h5>
                    <small>This is still in development and may not work as expected.</small>
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
                    <div class="col-6 py-2">
                        <div class="d-flex align-items-center justify-content-between" data-bs-toggle="tooltip"
                             data-bs-placement="top" data-bs-html="true"
                             title="This will automatically save the production line every 5 minutes.">
                            <label for="auto_save" class="form-label me-2">Auto Save</label>
                            <input type="checkbox" id="auto_save" class="from-control" data-onstyle="success"
                                   data-offstyle="danger"
                                   data-toggle="toggle" <?= $productionLineSettings->auto_save ? 'checked' : '' ?>>
                        </div>
                    </div>


                </div>
                <div class="row mb-3">
                    <label for="importFile" class="form-label">Import/Export</label>
                    <div class="col-8 pe-0">
                        <input type="file" id="importFile" name="file" accept=".json" class="form-control rounded-0 rounded-start" required>
                        <div class="invalid-feedback">Please select a valid JSON file.</div>
                    </div>
                    <div class="col-2 p-0">
                        <button class="btn btn-success w-100 rounded-0" id="importButton">Import</button>
                    </div>
                    <div class="col-2 ps-0">
                        <button class="btn btn-primary w-100 rounded-0 rounded-end" id="exportButton">Export</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" form="editProductionLineForm" class="btn btn-primary">Update Production Line</button>
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
