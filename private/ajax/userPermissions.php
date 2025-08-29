<?php
header('Content-Type: application/json');
if (!$_SERVER['REQUEST_METHOD'] === 'POST') {
    Response::error('Invalid request method', 405);
}
if (!isset($_POST['gameId']) || !isset($_POST['requestedUsers']) || !isset($_POST['allowedUsers'])) {
    Response::error('Missing parameters, please provide gameId, requestedUsers, and allowedUsers', 400);
}

$gameId = $_POST['gameId'];
$requestedUsers = json_decode($_POST['requestedUsers'], true);
$allowedUsers = json_decode($_POST['allowedUsers'], true);

if (!GameSaves::checkAccess($gameId, $_SESSION['userId'], Permission::SAVEGAME_INVITE)) {
    Response::error('Access denied', 403);
}

$gameSave = GameSaves::getSaveGameById($gameId);

if (!$gameSave) {
    Response::error('Game save not found', 404);
}

GameSaves::upsertUsersToSaveGame($requestedUsers, $gameId, 'requested');
GameSaves::upsertUsersToSaveGame($allowedUsers, $gameId, 'allowed');

$requestedUsers = GameSaves::getRequestedUsers($gameId);
$allowedUsers = GameSaves::getAllowedUsers($gameId);

$allowedUsers = array_filter($allowedUsers, fn($user) => $user->id !== $_SESSION['userId']);
$allowedUsers = array_filter($allowedUsers, fn($user) => $user->id !== $gameSave->owner_id);

Response::success([
    'requestedUsers' => $requestedUsers,
    'allowedUsers' => array_values($allowedUsers)
]);
