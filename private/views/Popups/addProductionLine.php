<?php
if ($_POST && isset($_POST['productionLineName'])) {
    // Assuming you've included or defined the Database class somewhere
    $gameSaveId = $_GET['id'];
    $productionLineName = $_POST['productionLineName'];

    // Assuming Database::insert() is a function that inserts data into the database
    $id = ProductionLines::addProductionline($gameSaveId, $productionLineName);
    if ($id) {
        header('Location:production_line?id=' . $id);
        exit();
    }
}
?>

<div class="modal fade" id="addProductionLine" tabindex="-1" aria-labelledby="popupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="popupModalLabel">Add production line</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="productionLineName" class="form-label">Production Line Name</label>
                        <input type="text" class="form-control" id="productionLineName" name="productionLineName"
                               required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Add Production Line</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    document.getElementById('add_product_line').addEventListener('click', function () {
        event.stopPropagation();
        const popoverProduction = $('#popover-production');

        $(document).ready(function () {
            popoverProduction.popover('hide');
            popoverProduction.attr('opened', 'true');
        });
        const addProductionLine = new bootstrap.Modal(document.getElementById('addProductionLine'));
        addProductionLine.show();
    });
</script>