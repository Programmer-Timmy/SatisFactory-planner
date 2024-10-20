<?php
if (!$_POST) {
    header('Location: /');
    exit();
}

$game_id = $_POST['gameId'];

// check if user is the owner of the game
$game = GameSaves::getSaveGameById($game_id);
if ($game->userId != $_SESSION['userId']) {
    header('Location: /');
    exit();
}

$search = $_POST['search'];

// Handle add, remove, and cancel requests
if (isset($_POST['addId'])) {
    $user_id = $_POST['addId'];
    GameSaves::addUserToSaveGame($user_id, $game_id);
}

if (isset($_POST['removeId'])) {
    $user_id = $_POST['removeId'];
    GameSaves::removeUserFromSaveGame($user_id, $game_id);
}

if (isset($_POST['cancelId'])) {
    $user_id = $_POST['cancelId'];
    GameSaves::removeUserFromSaveGame($user_id, $game_id);
}

// Get the updated lists
$allowedUsers = GameSaves::getAllowedUsers($game_id);
$requestUsers = GameSaves::getRequestedUsers($game_id);
$allUsers = Users::getAllUsers();
$users = Users::searchUsers($search);

// Filter out current session user from the lists
$data = Users::filterUsers($users, $allowedUsers, $requestUsers);
$users = $data['users'];
$allowedUsers = $data['allowedUsers'];
$requestUsers = $data['requestUsers'];

$allUsers = Users::filterUsers($allUsers, $allowedUsers, $requestUsers)['users'];

$allUsersCount = count($allUsers);

// Generate HTML for each section

// Return the generated HTML in JSON
$combinedHTML = '';

if (!empty($allowedUsers)) {
    $combinedHTML .= '<div class="mb-3">
                        <h6>Allowed users</h6>' .
        Users::generateUserListHTML($allowedUsers, $game_id, 'btn-danger remove_user', 'Remove user', 'removeId');
    '</div>';
}

if (!empty($requestUsers)) {
    $combinedHTML .= '<div class="mb-3">
                        <h6>Requested users</h6>' .
        Users::generateUserListHTML($requestUsers, $game_id, 'btn-warning cancel_request', 'Cancel request', 'cancelId');
    '</div>';
}

if (!empty($users) && $allUsersCount > 0) {
    $combinedHTML .= '<div class="mb-3">
                        <h6>Add user</h6>
                        <input type="text" name="Search1232" class="form-control mb-2" id="search_' . htmlspecialchars($game_id) . '"
                               placeholder="Search for user" value="' . htmlspecialchars($search) . '" autocomplete="off">
                                 <div class="users">
                        ' .
        Users::generateUserListHTML(array_slice($users, 0, 4), $game_id, 'btn-success send_request', 'Send request', 'addId');
    '</div></div>';
} elseif ($allUsersCount > 0) {
    $combinedHTML .= '<div class="mb-3">
                        <h6>Add user</h6>
                        <input type="text" name="Search1232" class="form-control mb-2" id="search_' . htmlspecialchars($game_id) . '"
                               placeholder="Search for user" value="' . htmlspecialchars($search) . '" autocomplete="off">
                               <div class="users">
                        <h6 class="text-center">No users found</h6>
                        </div>
                    </div>';
}

echo $combinedHTML;

exit();