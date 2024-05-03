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
?>

<div class="modal" id="editProductionLine" tabindex="-1" aria-labelledby="popupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="popupModalLabel">Update production line</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="productionLineName" class="form-label" >Production Line Name</label>
                        <input type="text" value="<?=$productLine->title?>" class="form-control" id="productionLineName" name="productionLineName"
                               required>
                    </div>
                    <div class="mb-3">
                        <label for="productionLineActive" class="form-label">Active</label><br>
                        <input type="checkbox" id="productionLineActive" class="from-control" data-onstyle="success" data-offstyle="danger" data-toggle="toggle" name="productionLineActive"
                               <?php if ($productLine->active) echo 'checked'; ?>>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update Production Line</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    document.getElementById('edit_product_line').addEventListener('click', function () {
        const addProductionLine = new bootstrap.Modal(document.getElementById('editProductionLine'));
        addProductionLine.show();
    });
</script>