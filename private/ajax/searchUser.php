<?php
header('Content-Type: application/json');

if (!isset($_POST['search'])) {
    Response::error('Search term is required', 400);
}

$gameIdProvided = !empty($_POST['gameId']);

if ($gameIdProvided && !GameSaves::checkAccessOwner($_POST['gameId'])) {
    Response::error('You do not have access to this save game', 403);
}

$search = htmlspecialchars($_POST['search']);
$selectedUsers = isset($_POST['selectedUsers']) ? explode(',', $_POST['selectedUsers']) : [];

if (!$gameIdProvided) {
    // Search without game context
    $users = Users::searchUsers($search);

    // Remove current user and selected users
    $users = array_filter($users, fn($user) => $user->id != $_SESSION['userId'] && !in_array($user->id, $selectedUsers));

    Response::success(array_values($users));

} else {
    // Game-related search
    $gameId = htmlspecialchars($_POST['gameId']);

    $allowedUsers = GameSaves::getAllowedUsers($gameId);
    $requestedUsers = GameSaves::getRequestedUsers($gameId);
    $users = Users::searchUsers($search);

    // remove the user that is the owner of the game
    $gameSave = GameSaves::getSaveGameById($gameId);
    if ($gameSave) {
        $users = array_filter($users, fn($user) => $user->id !== $gameSave->owner_id);
    }

    $filtered = Users::filterUsers($users, $allowedUsers, $requestedUsers);
    $users = $filtered['users'] ?? [];

    // Remove selected users
    $users = array_filter($users, fn($user) => !in_array($user->id, $selectedUsers));

    Response::success(array_values($users));
}
