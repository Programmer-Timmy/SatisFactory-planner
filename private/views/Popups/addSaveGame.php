<?php
ob_start();
if ($_POST && isset($_POST['saveGameName'])) {
    $saveGameName = $_POST['saveGameName'];
    var_dump($_FILES);
    if ($_POST['selectedUsers'] == null) {
        $_POST['selectedUsers'] = [];
    } else {
        $_POST['selectedUsers'] = explode(',', $_POST['selectedUsers']);
    }

    $gameSaveId = GameSaves::createSaveGame($_SESSION['userId'], $saveGameName, $_FILES['saveGameImage'], $_POST['selectedUsers']);

    if ($_POST['dedicatedServerIp'] && $_POST['dedicatedServerPort']) {
        $data = DedicatedServer::saveServer($gameSaveId, $_POST['dedicatedServerIp'], $_POST['dedicatedServerPort'], $_POST['dedicatedServerPassword']);
        if ($data) {
            if ($data['status'] === 'error') {
                $error = $data['message'];
            } else {
                $success = $data['message'];
            }
        }
    }

    if ($gameSaveId) {
        header('Location:/game_save?id=' . $gameSaveId);
        exit();
    }


}
$users = Users::getAllValidatedUsers();

?>

<div class="modal fade" id="popupModal" tabindex="-1" aria-labelledby="popupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="popupModalLabel">Add Save Game</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <!-- Other form fields for the save game -->
                    <div class="mb-3">
                        <label for="saveGameName" class="form-label">Save Game Name</label>
                        <input type="text" class="form-control" id="saveGameName" name="saveGameName" required>
                    </div>
                    <div class="mb-3">
                        <label for="saveGameImage" class="form-label">Save Game Image</label>
                        <input type="file" class="form-control" id="saveGameImage" name="saveGameImage">
                    </div>
                    <div id="selectedUsers" class="mt-3">
                        <h6 class="requested hidden">Requested Users</h6>
                        <div class="selected-users-list">
                        </div>
                    </div>

                    <div id="userList">
                        <div class="mb-3">
                            <h6>Add Users</h6>
                            <input type="search" class="form-control mb-2" id="addSearchUser"
                                   placeholder="Search for user" autocomplete="off" value="">
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
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Create Save Game</button>
                </div>
            </form>
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

        // Handle user search input
        document.getElementById('addSearchUser').addEventListener('input', function () {
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


</script>
