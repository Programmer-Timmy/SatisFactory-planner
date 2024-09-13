<?php
global $gameSave;
$users = Users::getAllValidatedUsers();
$allowedUsers = GameSaves::getAllowedUsers($gameSave->id);
$requestUsers = GameSaves::getRequestedUsers($gameSave->id);

// remove users that are already allowed
$users = array_filter($users, function ($user) use ($allowedUsers, $requestUsers) {
    // Check if the user is in allowedUsers or requestUsers, or if it's the current session user
    if (in_array($user->id, array_column($allowedUsers, 'users_id')) ||
        $user->id == $_SESSION['userId'] ||
        in_array($user->id, array_column($requestUsers, 'users_id'))) {
        return false;
    }
    return true;
});

?>
<div class="modal fade" id="UpdatedSaveGame_<?= $gameSave->id ?>" tabindex="-1" aria-labelledby="popupModalLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="popupModalLabel">Update save game</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $gameSave->id ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="UpdatedSaveGameName" class="form-label">Production Line Name</label>
                        <input type="text" class="form-control" id="UpdatedSaveGameName" name="UpdatedSaveGameName"
                               value="<?= $gameSave->title ?>"
                               required>
                    </div>
                    <div class="mb-3">
                        <label for="UpdatedSaveGameImage" class="form-label">Production Line Image</label>
                        <input type="file" class="form-control" id="UpdatedSaveGameImage" name="UpdatedSaveGameImage">
                    </div>
                    <div id="userList_<?= $gameSave->id ?>">
                        <?php if ($allowedUsers): ?>
                            <div class="mb-3">
                                <h6>Allowed users</h6>
                                <?php foreach ($allowedUsers as $user) : ?>
                                    <?php if ($user->users_id == $_SESSION['userId']) continue; ?>
                                    <div class="card mb-2 p-2">
                                        <div class="card-body d-flex justify-content-between align-items-center p-0">
                                            <h6 class="mb-1"><?= $user->username ?></h6>
                                            <button type="button" class="btn btn-danger remove_user"
                                                    user-id="<?= $user->users_id ?>"
                                                    game-id="<?= $gameSave->id ?>">Remove user
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($requestUsers): ?>
                            <div class="mb-3">
                                <h6>Requested users</h6>
                                <?php foreach ($requestUsers as $user) : ?>
                                    <?php if ($user->users_id == $_SESSION['userId']) continue; ?>
                                    <div class="card mb-2 p-2">
                                        <div class="card-body d-flex justify-content-between align-items-center p-0">
                                            <h6 class="mb-1"><?= $user->username ?></h6>
                                            <button type="button" class="btn btn-warning cancel_request"
                                                    user-id="<?= $user->users_id ?>"
                                                    game-id="<?= $gameSave->id ?>">Cancel request
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($users): ?>
                            <div class="mb-3">
                                <h6>Add user</h6>
                                <input type="text" class="form-control mb-2" id="searchUser_<?= $gameSave->id ?>"
                                       placeholder="Search for user">
                                <div class="users">
                                    <?php foreach (array_slice($users, 0, 5) as $user) : ?>
                                        <div class="card mb-2 p-2">
                                            <div class="card-body d-flex justify-content-between align-items-center p-0">
                                                <h6 class="mb-1"><?= $user->username ?></h6>
                                                <button type="button" class="btn btn-success send_request"
                                                        user-id="<?= $user->id ?>" game-id="<?= $gameSave->id ?>">Send
                                                    request
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
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
    console.log('add event listeners');

    // Function to handle AJAX requests
    function handleRequest(buttonId, requestData) {
        // preventing default

        // in userlist
        const buttons = document.getElementById('userList_<?= $gameSave->id ?>').querySelectorAll('.' + buttonId);
        for (let button of buttons) {
            if (button) {  // Check if the button exists
                button.addEventListener('click', function () {
                    // Get the search value if it exists or set it to an empty string
                    const search = document.getElementById('searchUser_<?= $gameSave->id ?>')
                        ? document.getElementById('searchUser_<?= $gameSave->id ?>').value
                        : '';

                    const userId = this.getAttribute('user-id');
                    const gameId = this.getAttribute('game-id');
                    const data = {
                        search: search,
                        gameId: gameId,
                    };
                    data[requestData] = userId; // Dynamically set the request parameter (addId, removeId, cancelId)

                    $.ajax({
                        url: 'requestHandeler',
                        type: 'POST',
                        data: data,
                        success: function (response) {
                            // apply changes to the user list
                            document.getElementById('userList_<?= $gameSave->id ?>').innerHTML = response;
                            // Add event listeners for each button
                            handleRequest('remove_user', 'removeId');
                            handleRequest('cancel_request', 'cancelId');
                            handleRequest('send_request', 'addId');
                            handleSearch();
                        }
                    });
                });
            }
        }
    }

    // Add event listeners for each button
    handleRequest('remove_user', 'removeId');
    handleRequest('cancel_request', 'cancelId');
    handleRequest('send_request', 'addId');

    function handleSearch() {
        // add search event listener
        const input = document.getElementById('searchUser_<?= $gameSave->id ?>')
        // Check if the input exists
        if (!input) return;

        input.addEventListener('input', function () {
            const search = this.value;
            const gameId = <?= $gameSave->id ?>;
            $.ajax({
                url: 'searchUser',
                type: 'POST',
                data: {
                    search: search,
                    gameId: gameId
                },
                success: function (response) {
                    // apply changes to the user list
                    document.getElementById('userList_<?= $gameSave->id ?>').querySelector('.users').innerHTML = response;
                    handleRequest('send_request', 'addId');
                }
            });
        });
    }

    handleSearch();
</script>

<script>
    document.getElementById('update_product_line_<?= $gameSave->id ?>').addEventListener('click', function () {
        const popupModal = new bootstrap.Modal(document.getElementById('UpdatedSaveGame_<?= $gameSave->id ?>'));
        popupModal.show();
    });
</script>


