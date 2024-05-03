<?php
ob_start();
if ($_POST && isset($_POST['saveGameName'])) {
    // Assuming you've included or defined the Database class somewhere
    ;
    $saveGameName = $_POST['saveGameName'];

    if ($_POST['AllowedUsers'] == null) {
        $_POST['AllowedUsers'] = [];
    }

    // Assuming Database::insert() is a function that inserts data into the database
    $gameSaveId = GameSaves::createSaveGame($_SESSION['userId'], $saveGameName, $_FILES['UpdatedSaveGameImage'], $_POST['AllowedUsers']);
    if ($gameSaveId) {
        header('Location:/game_save?id=' . $gameSaveId);
        exit();
    }

}
$users = Users::getAllUsers();

?>

<div class="modal" id="popupModal" tabindex="-1" aria-labelledby="popupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="popupModalLabel">Add production line</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="saveGameName" class="form-label">Production Line Name</label>
                        <input type="text" class="form-control" id="saveGameName" name="saveGameName"
                               required>
                    </div>
                    <div class="mb-3">
                        <label for="UpdatedSaveGameImage" class="form-label">Production Line Image</label>
                        <input type="file" class="form-control" id="UpdatedSaveGameImage" name="UpdatedSaveGameImage">
                    </div>

                <div class="mb-3">
                    <label for="Allowed users" class="form-label">Allowed users</label>
                    <!--                        multi select-->
                    <select class="form-select" name="AllowedUsers[]" multiple>
                        <?php foreach ($users as $user) : ?>
                            <?php if ($user->id == $_SESSION['userId']) continue; ?>
                            <option value="<?= $user->id ?>"><?= $user->username ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Add Production Line</button>
                </div>
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