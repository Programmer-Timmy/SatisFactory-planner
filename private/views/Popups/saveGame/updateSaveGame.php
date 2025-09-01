<?php
global $gameSave;
global $changelog;
$users = Users::getAllValidatedUsers();
$allowedUsers = GameSaves::getAllowedUsers($gameSave->id);
$requestUsers = GameSaves::getRequestedUsers($gameSave->id);

$allowedUsers = array_filter($allowedUsers, fn($user) => $user->id !== $gameSave->owner_id);

$data = Users::filterUsers($users, $allowedUsers, $requestUsers);
$roles = Roles::getAllRoles();
$users = $data['users'];
$allowedUsers = $data['allowedUsers'];
$requestUsers = $data['requestUsers'];

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
                        <label for="UpdatedSaveGameName" class="form-label fw-semibold">Save Game Name</label>
                        <input type="text" class="form-control" name="UpdatedSaveGameName"
                               value="<?= $gameSave->title ?>" placeholder="e.g. Big Little Factory"
                               required>
                    </div>
                    <div class="mb-4">
                        <label for="UpdatedSaveGameImage" class="form-label fw-semibold">Save Game Image <small
                                    class="text-muted">(optional)</small></label>
                        <?php if ($gameSave->image && $gameSave->image !== 'default_img.png'): ?>
                            <div class="mb-3">
                                <div class="card w-100">
                                    <img src="/image/<?= htmlspecialchars($gameSave->image) ?>" alt="Save Game Image"
                                         class="card-img-top img-fluid" style="max-height: 200px; object-fit: cover;">
                                    <div class="card-body p-2">
                                        <p class="card-text text-center mb-0">Current Save Image</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="UpdatedSaveGameImage" name="UpdatedSaveGameImage"
                               accept="image/*">
                        <div class="form-text">Uploading an image is optional but helps identify your save.</div>
                    </div>

                    <div id="userList_<?= $gameSave->id ?>">
                            <div class="mb-3">
                                <h6 class="allowed <?= $allowedUsers ? '' : 'hidden' ?>">Allowed users</h6>
                                <input type="hidden" name="allowed_users">
                                <div class="allowed-users-list">
                                </div>
                            </div>
                            <div class="mb-3 requested-users-container">
                                <h6 class="requested <?= $requestUsers ? '' : 'hidden' ?> ">Requested Users</h6>
                                <input type="hidden" name="requested_users">
                                <div class="requested-users-list">
                                </div>
                            </div>
                        <?php if ($users): ?>
                            <div class="mb-3 users-container">
                                <h6>Add user</h6>
                                <input type="text" style="display:none">
                                <input type="search" name="Search345" class="form-control mb-2"
                                       id="search_<?= $gameSave->id ?>"
                                       placeholder="Search for user" autocomplete="SearchUser1232">
                                <div class="users">
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
                                    <a href="game_saves?dedicatedServerId=<?= $gameSave->id ?>"
                                       class="btn btn-danger">Remove dedicated server</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-text">
                            Connect your save game to a dedicated server. This allows you to monitor the status of your
                            dedicated server directly within your save game dashboard.
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
<script src="/js/userSelect.js?v=<?= $changelog['version'] ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const element = $('#userList_<?= $gameSave->id ?>');
        const form = $('#updateSaveGameForm_<?= $gameSave->id ?>');
        const userSelect = new UserSelect(element, <?= json_encode($roles) ?>, form, <?= $gameSave->id ?> , <?= json_encode($allowedUsers) ?>, <?= json_encode($requestUsers) ?>);
    });
</script>

<script>
    document.getElementById('update_save_game_line_<?= $gameSave->id ?>').addEventListener('click', function () {
        const popupModal = new bootstrap.Modal(document.getElementById('UpdatedSaveGame_<?= $gameSave->id ?>'));
        popupModal.show();
    });
</script>


