<?php
global $gameSave;
require_once '../private/views/components/SaveGameView.php';

$users = Users::getAllValidatedUsers();
$allowedUsers = GameSaves::getAllowedUsers($gameSave->id);
$requestUsers = GameSaves::getRequestedUsers($gameSave->id);

$data = Users::filterUsers($users, $allowedUsers, $requestUsers);
$users = $data['users'];
$allowedUsers = $data['allowedUsers'];
$requestUsers = $data['requestUsers'];

$roles = Roles::getAllRoles();

$dedicatedServer = DedicatedServer::getBySaveGameId($gameSave->id);

if (isset($_GET['dedicatedServerId'])) {
    if (!GameSaves::checkAccessOwner($_GET['dedicatedServerId'])) {
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
                <form method="post" enctype="multipart/form-data" id="updateSaveGameForm_<?= $gameSave->id ?>"
                      autocomplete="off">
                    <input type="hidden" name="id" value="<?= $gameSave->id ?>">

                    <div class="mb-3">
                        <label for="UpdatedSaveGameName" class="form-label">Save Game Name</label>
                        <input type="text" class="form-control" name="UpdatedSaveGameName"
                               value="<?= $gameSave->title ?>"
                               required>
                    </div>
                    <div class="mb-3">
                        <label for="UpdatedSaveGameImage" class="form-label">Save Game Image</label>
                        <input type="file" class="form-control" name="UpdatedSaveGameImage">
                    </div>
                    <?php
                    (new SaveGameView())->renderUserList(
                        $allowedUsers,
                        $requestUsers,
                        $users,
                        $roles,
                        $gameSave,
                    ); ?>
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
    function handleRequest<?= $gameSave->id ?>(buttonId, requestData) {
        const token = $('meta[name="csrf-token"]').attr('content');
        if (!token) {
            console.error('CSRF token not found');
            return;
        }
        // preventing default

        // in userlist
        const buttons = document.getElementById('userList_<?= $gameSave->id ?>').querySelectorAll('.' + buttonId);
        console.log(buttons);
        for (let button of buttons) {
            console.log(button);
            if (button) {  // Check if the button exists

                button.addEventListener('click', function () {
                    // Get the search value if it exists or set it to an empty string
                    const search = document.getElementById('search_<?= $gameSave->id ?>')
                        ? document.getElementById('search_<?= $gameSave->id ?>').value
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
                        headers: {
                            'X-CSRF-TOKEN': token
                        },
                        success: function (response) {
                            // apply changes to the user list
                            document.getElementById('userList_<?= $gameSave->id ?>').innerHTML = response;
                            // Add event listeners for each button
                            handleRequest<?= $gameSave->id ?>('remove_user', 'removeId');
                            handleRequest<?= $gameSave->id ?>('cancel_request', 'cancelId');
                            handleRequest<?= $gameSave->id ?>('send_request', 'addId');
                            handleSearch<?= $gameSave->id ?>();
                        }
                    });
                });
            }
        }
    }

    // Add event listeners for each button
    handleRequest<?= $gameSave->id ?>('remove_user', 'removeId');
    handleRequest<?= $gameSave->id ?>('cancel_request', 'cancelId');
    handleRequest<?= $gameSave->id ?>('send_request', 'addId');

    function handleSearch<?= $gameSave->id ?>() {
        const token = $('meta[name="csrf-token"]').attr('content');
        if (!token) {
            console.error('CSRF token not found');
            return;
        }

        // add search event listener
        const input = document.getElementById('search_<?= $gameSave->id ?>')
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
                headers: {
                    'X-CSRF-TOKEN': token
                },
                success: function (response) {
                    // apply changes to the user list
                    document.getElementById('userList_<?= $gameSave->id ?>').querySelector('.users').innerHTML = response;
                    handleRequest<?= $gameSave->id ?>('send_request', 'addId');
                }
            });
        });
    }
    handleSearch<?= $gameSave->id ?>();
</script>

<script>
    document.getElementById('update_save_game_line_<?= $gameSave->id ?>').addEventListener('click', function () {
        const popupModal = new bootstrap.Modal(document.getElementById('UpdatedSaveGame_<?= $gameSave->id ?>'));
        popupModal.show();
    });
</script>

