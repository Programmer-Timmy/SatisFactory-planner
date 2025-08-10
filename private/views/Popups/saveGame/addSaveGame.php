<?php
$error = null;

if ($_POST && isset($_POST['saveGameName'])) {
    $saveGameName = trim($_POST['saveGameName']);

    // Validate Save Game Name
    if (strlen($saveGameName) > 45) {
        $error = 'Save Game Name is too lengthy. Please use up to 45 characters.';
    } elseif ($saveGameName !== strip_tags($saveGameName)) {
        $error = 'Security Alert: Unauthorized characters detected in Save Game Name. Nice try, but FICSIT Security has blocked that!';
    } elseif (isset($_FILES['saveGameImage']['tmp_name']) && $_FILES['saveGameImage']['tmp_name'] &&
        !in_array(mime_content_type($_FILES['saveGameImage']['tmp_name']), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
        $error = 'Invalid image format. Please upload an image in JPEG, PNG, GIF, or WebP format.';
    } elseif (isset($_FILES['saveGameImage']['tmp_name']) && $_FILES['saveGameImage']['tmp_name'] && $_FILES['saveGameImage']['size'] > 2097152) {
        $error = 'Image size is too large. Please upload an image under 2MB.';
    } elseif (isset($_FILES['saveGameImage']['name']) && $_FILES['saveGameImage']['name'] !== strip_tags($_FILES['saveGameImage']['name'])) {
        $error = 'Security Alert: Unauthorized characters detected in image name. Nice try, but FICSIT Security has blocked that!';
    }

    if (!$error) {
        // Handle selected users
        $selectedUsers = $_POST['selectedUsers'] ? explode(',', $_POST['selectedUsers']) : [];

        // Create save game
        $gameSaveId = GameSaves::createSaveGame($_SESSION['userId'], $saveGameName, $_FILES['saveGameImage'], $selectedUsers);
        //TODO: FIX THIS SO IT DOES NOT MAKE A SAVE GAME WHEN THERE IS AN ERROR

        // Validate dedicated server details if provided
        if (!empty($_POST['dedicatedServerIp']) && !empty($_POST['dedicatedServerPort'])) {
            if (!filter_var($_POST['dedicatedServerIp'], FILTER_VALIDATE_IP)) {
                if (!preg_match('/^(?!:\/\/)([a-zA-Z0-9-_]{1,63}\.)+[a-zA-Z]{2,6}$/', $_POST['dedicatedServerIp'])) {
                    $error = 'Invalid IP address or domain name';
                }
            } elseif (!is_numeric($_POST['dedicatedServerPort'])) {
                $error = 'Invalid port number';
            }

            // Save dedicated server if no error
            if (!$error) {
                $data = DedicatedServer::saveServer($gameSaveId, $_POST['dedicatedServerIp'], $_POST['dedicatedServerPort'], $_POST['dedicatedServerPassword']);
                if ($data) {
                    if ($data['status'] === 'error') {
                        $error = $data['message'];
                    } else {
                        $success = $data['message'];
                    }
                }
            }
        }

        // Redirect if successful
        if ($gameSaveId && !$error) {
            header('Location: /game_save?id=' . $gameSaveId);
            exit();
        }
    }
}

// Fetch users for selection
$users = Users::getAllValidatedUsers();
?>

<div class="modal fade <?= $error ? 'show' : '' ?>" id="popupModal" tabindex="-1" aria-labelledby="popupModalLabel"
     aria-modal="true" role="dialog" style="display: <?= $error ? 'block' : 'none' ?>;">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="popupModalLabel">Add Save Game</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" enctype="multipart/form-data" autocomplete="off" id="addSaveGameForm">
                    <!-- Other form fields for the save game -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="saveGameName" class="form-label fw-semibold">Save Game Name</label>
                        <input type="text" class="form-control" id="saveGameName" name="saveGameName" required
                               maxlength="45" placeholder="e.g. Big Little Factory"
                               value="<?= isset($saveGameName) ? htmlspecialchars($saveGameName) : '' ?>">
                    </div>
                    <div class="mb-3">
                        <label for="saveGameImage" class="form-label fw-semibold">Save Game Image <small
                                    class="text-muted">(optional)</small></label>
                        <input type="file" class="form-control" id="saveGameImage" name="saveGameImage">
                        <div class="form-text">Uploading an image is optional but helps identify your save.</div>

                    </div>
                    <div id="selectedUsers" class="mt-3">
                        <h6 class="requested hidden">Requested Users</h6>
                        <div class="selected-users-list">
                        </div>
                    </div>

                    <div id="userList">
                        <div class="mb-3">
                            <h6>Add Users</h6>
                            <input type="text" style="display:none">
                            <input type="search" name="Search1232" class="form-control mb-2" id="addSearch"
                                   placeholder="Type to find a user..." autocomplete="off">
                            <div class="users">
                                <!--                                max of 5-->
                                <?php foreach (array_slice($users, 0, 5) as $user) : ?>
                                    <?php if ($user->id == $_SESSION['userId']) continue; ?>
                                    <div class="card mb-2 p-2">
                                        <div class="card-body d-flex justify-content-between align-items-center p-0">
                                            <h6 class="mb-1"><?= $user->username ?></h6>
                                            <button type="button" class="btn btn-success add_user"
                                                    data-user-id="<?= $user->id ?>">Add User
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="form-text">
                                    <?= count($users) > 5 ? 'And ' . (count($users) - 5) . ' more. Search for more users.' : ''?>
                                </div>
                            </div>
                        </div>
                        <!-- Hidden input to store selected user IDs -->
                        <input type="hidden" name="selectedUsers" id="selectedUsersInput">
                    </div>
                    <div class="mb-3">
                        <button class="btn btn-success w-100 dedicatedServerButton" type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#dedicatedServerCollapse" aria-expanded="false"
                                aria-controls="dedicatedServerCollapse">
                            <i class="fas fa-server"></i>
                            Add Dedicated Server Credentials
                        </button>
                        <div class="collapse" id="dedicatedServerCollapse">
                            <div class="card card-body rounded-top-0">
                                <div class="mb-3">
                                    <label for="dedicatedServerIp" class="form-label">Server IP</label>
                                    <input type="text" class="form-control"
                                           name="dedicatedServerIp" autocomplete="off"
                                           placeholder="Enter your server IP">
                                </div>
                                <div class="mb-3">
                                    <label for="dedicatedServerPort" class="form-label">Server Port</label>
                                    <input type="text" class="form-control"
                                           name="dedicatedServerPort" autocomplete="off" value="7777">
                                </div>
                                <div class="mb-3">
                                    <label for="dedicatedServerPassword" class="form-label mb-0">Server Client
                                        Password</label><br>
                                    <small class="text-muted mb-2" id="passwordHelp">Leave empty if no password is
                                        set.</small>
                                    <div class="input-group">
                                        <input type="password"
                                               class="form-control dedicatedServerPassword"
                                               name="dedicatedServerPassword"
                                               placeholder="Enter your password"
                                               autocomplete="off"
                                               aria-describedby="passwordHelp"
                                               aria-required="false">
                                        <button class="btn btn-outline-secondary togglePassword" type="button"
                                                id="togglePassword"
                                                aria-label="Toggle password visibility " style="width: 45px"
                                                autocomplete="off">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="form-text">
                            Connect your save game to a dedicated server. This allows you to monitor the status of
                            your
                            dedicated server directly within your save game dashboard.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary" form="addSaveGameForm"
                        data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true"
                        title="Create a new save game with the provided details.">
                    <i class="fas fa-save"></i>
                    <span class="d-none d-md-inline">Create Save Game</span>
                </button>
            </div>
        </div>
    </div>
</div>
<script>
    document.getElementById('add_game_save').addEventListener('click', function () {
        const popupModal = new bootstrap.Modal(document.getElementById('popupModal'));
        popupModal.show();
    });

    document.addEventListener('DOMContentLoaded', function () {
        const selectedUsers = [];
        const selectedUsersInput = document.getElementById('selectedUsersInput');
        const token = $('meta[name="csrf-token"]').attr('content');
        if (!token) {
            console.error('CSRF token not found');
        }

        // Handle user search input
        document.getElementById('addSearch').addEventListener('input', function () {
            const search = this.value;
            // AJAX call to search for users
            $.ajax({
                url: 'searchUser', // Ensure this is the correct path
                type: 'POST',
                data: {
                    add: true,
                    search: search,
                    selectedUsers: selectedUsers.join(',')
                },
                headers: {
                    'X-CSRF-Token': token
                },
                success: function (response) {
                    // Populate the .users container with the search results
                    $('#userList .users').html(response);
                    handleAddUserButtons(); // Re-bind events to new buttons
                },
                error: function () {
                    console.error('Error during search');
                }
            });
        });

        // Function to handle Add User button clicks
        function handleAddUserButtons() {
            const addUserButtons = document.querySelectorAll('.add_user');
            addUserButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const userId = this.getAttribute('data-user-id');
                    const username = this.previousElementSibling.textContent;

                    if (!selectedUsers.includes(userId)) {
                        // Add user to the selected list
                        selectedUsers.push(userId);
                        updateSelectedUsersDisplay(username, userId);
                        updateHiddenInput();

                        // Remove user from selectable list
                        this.closest('.card').remove();
                    }

                    // if this is the first user added, show the requested users section
                    if (selectedUsers.length === 1) {
                        document.querySelector('.requested').classList.remove('hidden');
                    }

                    // if users is now empty, show no users found
                    if (document.querySelectorAll('.add_user').length === 0) {
                        $('#userList .users').html('<h6 class="text-center">No users found</h6>');
                    }
                });
            });
        }

        // Update the display for selected users
        function updateSelectedUsersDisplay(username, userId) {
            const selectedUsersList = document.querySelector('.selected-users-list');
            const userElement = document.createElement('div');
            userElement.classList.add('card', 'mb-2', 'p-2');
            userElement.innerHTML = `
            <div class="card-body d-flex justify-content-between align-items-center p-0">
                <h6 class="mb-1">${username}</h6>
                <button type="button" class="btn btn-warning remove_user" data-user-id="${userId}">Cancel request</button>
            </div>
        `;
            selectedUsersList.appendChild(userElement);

            // Handle remove button click
            userElement.querySelector('.remove_user').addEventListener('click', function () {
                removeUser(userId, userElement, username);
            });
        }

        // Remove user from selected list and add back to selectable list
        function removeUser(userId, userElement, username) {
            const index = selectedUsers.indexOf(userId);
            if (index !== -1) {
                selectedUsers.splice(index, 1);
                userElement.remove();
                updateHiddenInput();

                // Re-add user to the selectable list
                const userCard = document.createElement('div');
                userCard.classList.add('card', 'mb-2', 'p-2');
                userCard.innerHTML = `
                <div class="card-body d-flex justify-content-between align-items-center p-0">
                    <h6 class="mb-1">${username}</h6>
                    <button type="button" class="btn btn-success add_user" data-user-id="${userId}">Add User</button>
                </div>
            `;
                // if this is the last user removed, hide the requested users section
                if (selectedUsers.length === 0) {
                    document.querySelector('.requested').classList.add('hidden');
                }

                if (document.querySelectorAll('.add_user').length === 0) {
                    $('#userList .users').find('h6').remove();
                }

                $('#userList .users').append(userCard);


                // Re-bind the event listener for the re-added user
                userCard.querySelector('.add_user').addEventListener('click', function () {
                    const userId = this.getAttribute('data-user-id');
                    const username = this.previousElementSibling.textContent;

                    if (!selectedUsers.includes(userId)) {
                        // Add user to the selected list
                        selectedUsers.push(userId);
                        updateSelectedUsersDisplay(username, userId);
                        updateHiddenInput();

                        // Remove user from selectable list
                        this.closest('.card').remove();
                    }
                });
            }
        }

        // Update the hidden input with the selected user IDs
        function updateHiddenInput() {
            selectedUsersInput.value = selectedUsers.join(',');
        }

        // Initial binding for Add User buttons
        handleAddUserButtons();
    });

    <?php if ($error): ?>
    document.addEventListener('DOMContentLoaded', function () {
        const popupModal = new bootstrap.Modal(document.getElementById('popupModal'));
        popupModal.show();
    });
    <?php endif; ?>
</script>
