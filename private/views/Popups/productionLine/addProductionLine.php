<?php
$error = '';
if ($_POST && isset($_POST['productionLineName'])) {
    // Assuming the Database and ProductionLines classes are included
    $gameSaveId = $_GET['id'];
    $productionLineName = $_POST['productionLineName'];

    // Validate Production Line Name Length
    if (strlen($productionLineName) > 45) {
        $error = 'Production Line Name is too lengthy. Please use up to 45 characters.';
    } elseif ($productionLineName !== strip_tags($productionLineName)) {
        $error = 'Security Alert: Unauthorized characters detected in Production Line Name. Nice try, but FICSIT Security has blocked that!';
    }

    // Process only if there is no error
    if (!$error) {
        // Sanitize Production Line Name
        $productionLineName = htmlspecialchars($productionLineName);

        // Insert into database
        $id = ProductionLines::addProductionline($gameSaveId, $productionLineName);
        if ($id) {
            header('Location: production_line?id=' . $id);
            exit();
        } else {
            $error = 'Error adding production line. Please try again or contact support.';
        }
    }
}
?>

<div class="modal fade <?= $error ? 'show d-block' : '' ?>" id="addProductionLine" tabindex="-1"
     aria-labelledby="popupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="popupModalLabel">Add production line</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="productionLineName" class="form-label">Production Line Name</label>
                        <input type="text" class="form-control" id="productionLineName" name="productionLineName"
                               required maxlength="45"
                               value="<?= isset($productionLineName) ? htmlspecialchars($productionLineName) : '' ?>">
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
    document.getElementById('add_product_line').addEventListener('click', function (event) {
        event.stopPropagation();
        const popoverProduction = $('#popover-production');

        $(document).ready(function () {
            popoverProduction.popover('hide');
            popoverProduction.attr('opened', 'true');
        });
        const addProductionLine = new bootstrap.Modal(document.getElementById('addProductionLine'));
        addProductionLine.show();
    });

    <?php if ($error): ?>
    $(document).ready(function () {
        const popoverProduction = $('#popover-production');
        popoverProduction.popover('hide');
        popoverProduction.attr('opened', 'true');
    });
    <?php endif; ?>
</script>
