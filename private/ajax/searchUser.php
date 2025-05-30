<?php
require_once '../private/views/components/SaveGameView.php';
if (!isset($_POST['search']) && !isset($_POST['gameId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

if (isset($_POST['gameId']) && !GameSaves::checkAccessOwner($_POST['gameId'])) {
    http_response_code(403);
    echo json_encode(['error' => 'You do not have access to this save game']);
    exit();
}

$game_id = htmlspecialchars($_POST['gameId']);
$roles = Roles::getAllRoles();
$gameSave = GameSaves::getSaveGameById($game_id);

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
        (new SaveGameView())->renderAddUser(
            $users,
            $roles,
            $gameSave,
            false
        ) ;
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
                (new SaveGameView())->renderAddUser(
                    $user,
                    $roles,
                    $gameSave
                ) ;
            }

        }
    }
}

