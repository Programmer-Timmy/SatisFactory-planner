<?php
if (!isset($_POST['search'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Search term is required']);
    exit();
}

$gameIdProvided = isset($_POST['gameId']) && $_POST['gameId'] !== '';

if ($gameIdProvided && !GameSaves::checkAccessOwner($_POST['gameId'])) {
    http_response_code(403);
    echo json_encode(['error' => 'You do not have access to this save game']);
    exit();
}

$search = htmlspecialchars($_POST['search']);
$selectedUsers = isset($_POST['selectedUsers']) ? explode(',', $_POST['selectedUsers']) : [];

if (!$gameIdProvided) {
    // Search without game-related filtering
    $users = Users::searchUsers($search);

    // Remove current user
    $users = array_filter($users, function ($user) {
        return $user->id != $_SESSION['userId'];
    });

    // Remove selected users
    $users = array_filter($users, function ($user) use ($selectedUsers) {
        return !in_array($user->id, $selectedUsers);
    });

    if (empty($users)) {
        echo '<h6 class="text-center">No users found</h6>';
    } else {
        foreach ($users as $user) {
            echo '<div class="card mb-2 p-2">
                <input type="text" style="display:none">
                <div class="card-body d-flex justify-content-between align-items-center p-0">
                    <h6 class="mb-1">' . htmlspecialchars($user->username) . '</h6>
                    <button type="button" class="btn btn-success add_user" data-user-id="' . $user->id . '">Add User</button>
                </div>
            </div>';
        }
    }
} else {
    // Game-related search
    $game_id = htmlspecialchars($_POST['gameId']);

    $allowedUsers = GameSaves::getAllowedUsers($game_id);
    $requestUsers = GameSaves::getRequestedUsers($game_id);
    $users = Users::searchUsers($search);

    $data = Users::filterUsers($users, $allowedUsers, $requestUsers);
    $users = $data['users'];

    if (!empty($users)) {
        echo Users::generateUserListHTML(array_slice($users, 0, 4), $game_id, 'btn-success send_request', 'Send request', 'addId');
    } else {
        echo '<h6 class="text-center">No users found</h6>';
    }
}
