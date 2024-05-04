<?php
global $gameSave;
$users = Users::getAllUsers();
$allowedUsers = GameSaves::getAllowedUsers($gameSave->id);
?>
<div class="modal fade" id="UpdatedSaveGame_<?= $gameSave->id ?>" tabindex="-1" aria-labelledby="popupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="popupModalLabel">Update save game<h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
<!--            add img upload-->
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $gameSave->id ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="UpdatedSaveGameName" class="form-label">Production Line Name</label>
                        <input type="text" class="form-control" id="UpdatedSaveGameName" name="UpdatedSaveGameName" value="<?= $gameSave->title ?>"
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
                                <option value="<?= $user->id ?>" <?php if (in_array($user->id, $allowedUsers)) echo 'selected' ?>><?= $user->username ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update save game</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    document.getElementById('update_product_line_<?= $gameSave->id ?>').addEventListener('click', function () {
        const popupModal = new bootstrap.Modal(document.getElementById('UpdatedSaveGame_<?= $gameSave->id ?>'));
        popupModal.show();
    });
</script>
