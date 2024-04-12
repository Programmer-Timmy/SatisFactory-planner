<?php
if ($_POST && isset($_POST['saveGameName'])) {
    // Assuming you've included or defined the Database class somewhere
    ;
    $saveGameName = $_POST['saveGameName'];

    // Assuming Database::insert() is a function that inserts data into the database
    $gameSaveId = GameSaves::createSaveGame($_SESSION['userId'], $saveGameName);
    if ($gameSaveId) {
        header('Location: game_save?id=' . $gameSaveId);
        exit();
    }

}
?>

<div class="modal" id="popupModal" tabindex="-1" aria-labelledby="popupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="popupModalLabel">Add production line</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="saveGameName" class="form-label">Production Line Name</label>
                        <input type="text" class="form-control" id="saveGameName" name="saveGameName"
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
        const popupModal = new bootstrap.Modal(document.getElementById('popupModal'));
        popupModal.show();
    });
</script>