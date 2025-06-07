<?php
class SaveGameView {

    public function renderUserList($allowedUsers, $requestUsers, $users, $roles, $gameSave, $renderInList = true): void
    {
        if ($renderInList) {
            ?>
                <div id="userList_<?= htmlspecialchars($gameSave->id) ?>">
            <?php
        };
        ?>
            <?php
            $this->renderAllowedUsers($allowedUsers, $roles, $gameSave);
            $this->renderRequestedUsers($requestUsers, $roles, $gameSave);
            $this->renderAddUser($users, $roles, $gameSave);
            ?>
        <?php
        if ($renderInList) {
            ?>
                </div>
            <?php
        }
    }

    private function renderUserCard($user, $roles, $gameSave, $buttonType, $buttonClass, $buttonLabel): void {
        ?>
        <div class="card mb-2 p-2">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 rounded p-0">
                <div class="flex-fill">
                    <h6 class="mb-1"><?= htmlspecialchars($user->username) ?></h6>
                    <select class="form-select" name="role_<?= $user->id ?>"
                            aria-label="Select role for <?= htmlspecialchars($user->username) ?>">
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role->id ?>"
                                <?= ($user->role_id ?? 2) == $role->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role->name) ?> (<?= htmlspecialchars($role->description) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button type="button" class="btn <?= $buttonClass ?> <?= $buttonType ?>"
                            user-id="<?= $user->users_id ?? $user->id ?>"
                            game-id="<?= htmlspecialchars($gameSave->id) ?>">
                        <?= $buttonLabel ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    private function renderAllowedUsers($allowedUsers, $roles, $gameSave): void {
        if (!$allowedUsers) return;
        ?>
        <div class="mb-3">
            <h6>Allowed users</h6>
            <?php foreach ($allowedUsers as $user) : ?>
                <?php $this->renderUserCard($user, $roles, $gameSave, 'remove_user', 'btn-outline-danger', 'Remove user'); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function renderRequestedUsers($requestUsers, $roles, $gameSave): void {
        if (!$requestUsers) return;
        ?>
        <div class="mb-3">
            <h6>Requested users</h6>
            <?php foreach ($requestUsers as $user) : ?>
                <?php $this->renderUserCard($user, $roles, $gameSave, 'cancel_request', 'btn-outline-warning', 'Cancel request'); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    public function renderAddUser($users, $roles, $gameSave, $input = true): void {
        ?>
        <div class="mb-3">
            <h6>Add user</h6>
            <?php if ($users): ?>
            <?php if ($input): ?>
                <input type="text" style="display:none">
                <input type="search" name="Search345" class="form-control mb-2"
                       id="search_<?= htmlspecialchars($gameSave->id) ?>"
                       placeholder="Search for user" autocomplete="SearchUser1232">
            <?php endif; ?>
                <div class="users">
                    <?php foreach (array_slice($users, 0, 4) as $user) : ?>
                        <?php $this->renderUserCard($user, $roles, $gameSave, 'send_request', 'btn-success', 'Send request'); ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No users available</p>
            <?php endif; ?>
        </div>
        <?php
    }
}
