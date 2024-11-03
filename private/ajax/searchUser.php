<?php
if (!isset($_POST['search']) && !isset($_POST['gameId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

if (!GameSaves::checkAccessOwner($_POST['gameId'])) {
    http_response_code(403);
    echo json_encode(['error' => 'You do not have access to this save game']);
    exit();
}


if (!isset($_POST['add'])) {
    $game_id = htmlspecialchars($_POST['gameId']);
    $search = htmlspecialchars($_POST['search']);

// Fetch the user lists
    $allowedUsers = GameSaves::getAllowedUsers($game_id);
    $requestUsers = GameSaves::getRequestedUsers($game_id);
    $users = Users::searchUsers($search);

// Filter out current session user from the lists
    $data = Users::filterUsers($users, $allowedUsers, $requestUsers);
    $users = $data['users'];
    $allowedUsers = $data['allowedUsers'];
    $requestUsers = $data['requestUsers'];

    if (!empty($users)) {
        // Generate user list if users are found
        echo Users::generateUserListHTML(array_slice($users, 0, 4), $game_id, 'btn-success send_request', 'Send request', 'addId');
    } else {
        // Display "No users found" if the search result is empty
        echo '<h6 class="text-center">No users found</h6>';
    }

} else {

// searchUser.php
    $search = $_POST['search'];
    $selectedUsers = isset($_POST['selectedUsers']) ? explode(',', $_POST['selectedUsers']) : [];

// Fetch users from the database based on search input (you need to replace this with actual query)
    $users = Users::searchUsers($search);

// Filter out current session user from the list
    $users = array_filter($users, function ($user) {
        return $user->id != $_SESSION['userId'];
    });

// remove selected users from the list
    $users = array_filter($users, function ($user) use ($selectedUsers) {
        return !in_array($user->id, $selectedUsers);
    });

    if (empty($users)) {
        echo '<h6 class="text-center">No users found</h6>';
    } else {
        foreach ($users as $user) {
            if ($user->id != $_SESSION['userId']) {
                echo '<div class="card mb-2 p-2">
                <input type="text" style="display:none">
                <div class="card-body d-flex justify-content-between align-items-center p-0">
                    <h6 class="mb-1">' . $user->username . '</h6>
                    <button type="button" class="btn btn-success add_user" data-user-id="' . $user->id . '">Add User</button>
                </div>
            </div>';
            }
        }
    }
}

