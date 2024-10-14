<?php
global $gameSave;
$users = Users::getAllValidatedUsers();
$allowedUsers = GameSaves::getAllowedUsers($gameSave->id);
$requestUsers = GameSaves::getRequestedUsers($gameSave->id);

$data = Users::filterUsers($users, $allowedUsers, $requestUsers);
$users = $data['users'];
$allowedUsers = $data['allowedUsers'];
$requestUsers = $data['requestUsers'];

$dedicatedServer = DedicatedServer::getBySaveGameId($gameSave->id);

if (isset($_GET['dedicatedServerId'])) {
    if (!GameSaves::checkecsess($_GET['dedicatedServerId'])) {
        header('Location:/home');
        exit();
    }

    DedicatedServer::deleteServer($_GET['dedicatedServerId']);
    header('Location:/home');
    exit();
}
?>
<div class="modal fade" id="UpdatedSaveGame_<?= $gameSave->id ?>" tabindex="-1" aria-labelledby="popupModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="popupModalLabel">Update save game</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" enctype="multipart/form-data" id="updateSaveGameForm_<?= $gameSave->id ?>">
                    <input type="hidden" name="id" value="<?= $gameSave->id ?>">

                    <div class="mb-3">
                        <label for="UpdatedSaveGameName" class="form-label">Production Line Name</label>
                        <input type="text" class="form-control" name="UpdatedSaveGameName"
                               value="<?= $gameSave->title ?>"
                               required>
                    </div>
                    <div class="mb-3">
                        <label for="UpdatedSaveGameImage" class="form-label">Production Line Image</label>
                        <input type="file" class="form-control" name="UpdatedSaveGameImage">
                    </div>
                    <div id="userList_<?= $gameSave->id ?>">
                        <?php if ($allowedUsers): ?>
                            <div class="mb-3">
                                <h6>Allowed users</h6>
                                <?php foreach ($allowedUsers as $user) : ?>
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
                                <input type="search" class="form-control mb-2" id="searchUser_<?= $gameSave->id ?>"
                                       placeholder="Search for user" autocomplete="off" value="">
                                <div class="users">
                                    <?php foreach (array_slice($users, 0, 4) as $user) : ?>
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
                        <?php else: ?>
                            <div class="mb-3">
                                <h6>Add user</h6>
                                <p>No users available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <button class="btn btn-primary w-100 dedicatedServerButton" type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#dedicatedServerCollapse<?= $gameSave->id ?>" aria-expanded="false"
                                aria-controls="dedicatedServerCollapse<?= $gameSave->id ?>">
                            <i class="fas fa-server"></i>
                            Edit dedicated server credentials
                        </button>
                        <div class="collapse" id="dedicatedServerCollapse<?= $gameSave->id ?>">
                            <div class="card card-body rounded-top-0">
                                <div class="mb-3">
                                    <label for="dedicatedServerIp" class="form-label">Server IP</label>
                                    <input type="text" class="form-control"
                                           name="dedicatedServerIp" autocomplete="off"
                                           value="<?= $dedicatedServer ? $dedicatedServer->server_ip : '' ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="dedicatedServerPort" class="form-label">Server Port</label>
                                    <input type="text" class="form-control"
                                           name="dedicatedServerPort" autocomplete="off"
                                           value="<?= $dedicatedServer ? $dedicatedServer->server_port : '7777' ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="dedicatedServerPassword" class="form-label mb-0">Server Client
                                        Password</label><br>
                                    <small class="text-muted mb-2" id="passwordHelp">Leave empty if no password is
                                        set.</small>
                                    <div class="input-group">
                                        <input type="password"
                                               class="form-control passwordInput"
                                               name="dedicatedServerPassword"
                                               placeholder="Enter your password"
                                               autocomplete="off"
                                               aria-describedby="passwordHelp"
                                               aria-required="false">
                                        <button class="btn btn-outline-secondary togglePassword" type="button"
                                                aria-label="Toggle password visibility" style="width: 45px"
                                                autocomplete="off">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <?php if ($dedicatedServer): ?>
                                    <a href="home?dedicatedServerId=<?= $gameSave->id ?>"
                                       class="btn btn-danger">Remove dedicated server</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="modal-footer pb-0 px-0">
                    <button type="submit" class="btn btn-primary me-0" form="updateSaveGameForm_<?= $gameSave->id ?>">
                        Update
                        Save Game
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
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
    document.getElementById('update_save_game_line_<?= $gameSave->id ?>').addEventListener('click', function () {
        const popupModal = new bootstrap.Modal(document.getElementById('UpdatedSaveGame_<?= $gameSave->id ?>'));
        popupModal.show();
    });
</script>


