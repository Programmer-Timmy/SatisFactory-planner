<?php

require_once '../private/types/permission.php';


class GameSaves {
    /**
     * @param int $owner_id
     * @return mixed
     */
    public static function getSaveGameByOwner(int $owner_id) {
        return Database::getAll("game_saves", ['*'], [], ['owner_id' => $owner_id]);
    }

    /**
     * Check if a user has a specific role for a game save
     *
     * @param int $gameSaveId The id of the game save
     * @param int $userId The id of the user
     * @param Permission $permission The permission to check
     * @param bool $negate If true, the function will return true if the user does NOT have the role
     * @param bool $onlyThisPermission If true, the function will return true only if the user has exactly this permission and no others
     * @return ?bool True if the user has the role, false if not, null if the user has no roles for this game save
     */
    public static function checkAccess(
        int $gameSaveId,
        int $userId,
        Permission $permission,
        bool $negate = false,
        bool $onlyThisPermission = false
    ): ?bool {
        // Fetch all permissions of this user for this game save
        $permissions = Database::getAll(
            "users_has_game_saves",
            ['permissions.name as permission'],
            [
                'roles' => 'roles.id = users_has_game_saves.role_id',
                'role_permission' => 'role_permission.role_id = roles.id',
                'permissions' => 'permissions.id = role_permission.permission_id'
            ],
            [
                'game_saves_id' => $gameSaveId,
                'users_id' => $userId,
                'accepted' => 1,
            ]
        );

        if (!$permissions || count($permissions) === 0) {
            return null;
        }

        $permissionNames = array_column($permissions, 'permission');
        $hasPermission = in_array($permission->value, $permissionNames, true);

        if ($onlyThisPermission) {
            // Return true only if the user has exactly this permission and no others
            $hasPermission = $hasPermission && count($permissionNames) === 1;
        }

        return $negate ? !$hasPermission : $hasPermission;
    }




    /**
     * @param int $user_id
     * @return mixed
     */
    public static function getSaveGamesByUser(int $user_id) {
        $gameSaves = Database::getAll(
            "users_has_game_saves",
            [
                'game_saves_id',
                'title',
                'created_at',
                'username as Owner',
                'image',
                'game_saves.id',
                'game_saves.owner_id',
                'hidden',
                // JSON array of permission names
                '(SELECT JSON_ARRAYAGG(p.name)
          FROM role_permission rp
          JOIN permissions p ON p.id = rp.permission_id
          WHERE rp.role_id = users_has_game_saves.role_id
        ) AS permissions',
                '(SELECT COUNT(*) FROM production_lines WHERE game_saves_id = game_saves.id) as production_lines'
            ],
            [
                'game_saves' => 'game_saves.id = users_has_game_saves.game_saves_id',
                'users' => 'users.id = game_saves.owner_id',
                // roles are still joined to get role_id
                'roles' => 'roles.id = users_has_game_saves.role_id'
            ],
            [
                'users_has_game_saves.users_id' => $user_id,
                'accepted' => 1
            ]
        );

        // Decode the JSON array of permission names
        foreach ($gameSaves as $gameSave) {
            $gameSave->permissions = json_decode($gameSave->permissions, true) ?: [];
        }

        return $gameSaves;
    }

    /**
     * @param int $id
     * @return mixed
     */
    public static function getSaveGameById(int $id, NewDatabase|null $database = null) {
        $database = $database ?? new NewDatabase();
        return $database->get("users_has_game_saves",
                              ['game_saves.*', 'users.username', 'users.id as userId', 'users_has_game_saves.card_view', 'roles.name as role'],
                              [
                                  'game_saves' => 'game_saves.id = users_has_game_saves.game_saves_id', "users" => "game_saves.owner_id = users.id",
                                  'roles' => 'roles.id = users_has_game_saves.role_id'
                              ],
                              ['game_saves.id' => $id, 'accepted' => 1, 'users_has_game_saves.users_id' => $_SESSION['userId']]);
    }

    /**
     * @param int $id
     * @return mixed
     */
    public static function getSaveGameByIdAdmin(int $id) {
        return Database::get("users_has_game_saves", ['game_saves.*', 'users.username', 'users.id as userId', 'users_has_game_saves.card_view'], ['game_saves' => 'game_saves.id = users_has_game_saves.game_saves_id', "users" => "game_saves.owner_id = users.id"], ['game_saves.id' => $id]);
    }

    /**
     * @param int $user_id
     * @param string $title
     * @param array $image
     * @param array $allowedUsers
     * @return string
     * @throws ErrorException
     */
    public static function createSaveGame(int $user_id, string $title, array $image, array $allowedUsers) {
        $createdAt = date('Y-m-d H:i:s');
        $id = Database::insert("game_saves", ['owner_id', 'title', 'created_at', 'total_power_production'], [$user_id, $title, $createdAt, 0]);
        Database::insert("users_has_game_saves", ['users_id', 'game_saves_id', 'role_id'], [$user_id, $id, 1]);

        if ($image['tmp_name'] != '') {
            self::uploadImage($id, $image);
        }

        foreach ($allowedUsers as $user) {
            self::addUserToSaveGame($user, $id);
        }

        return $id;
    }

    /**
     * @param array $user
     * @param int $game_save_id
     * @return null
     * @throws ErrorException
     */
    public static function addUserToSaveGame(array $user, int $game_save_id, NewDatabase $database = null) {
        $database = $database ?? new NewDatabase();
        $database->insert("users_has_game_saves", ['users_id', 'role_id', 'game_saves_id', 'accepted'], [$user['id'], $user['roleId'], $game_save_id, 0]);

    }

    public static function upsertUsersToSaveGame(array $users, int $game_save_id, string $type) {

        $database = new NewDatabase();
        $database->beginTransaction();
        $accepted = ($type == 'allowed') ? 1 : 0;
        $game_save = Database::get("game_saves", ['owner_id'], [], ['id' => $game_save_id]);
        try {

            foreach ($users as $user) {
                if ($user['id'] == $_SESSION['userId'] || $user['id'] == $game_save->owner_id) {
                    continue; // Skip current user
                }
                $existing = $database->get("users_has_game_saves", ['id'], [], ['users_id' => $user['id'], 'game_saves_id' => $game_save_id]);
                if ($existing) {
                    $database->update("users_has_game_saves", ['role_id', 'accepted'], [$user['roleId'], $accepted], ['id' => $existing->id]);
                } else {
                    self::addUserToSaveGame($user, $game_save_id, $database);
                }
            }

            // Remove users that are not in the new list
            $userIds = array_column($users, 'id');
            $userIds[] = $_SESSION['userId']; // Keep the owner always
            $userIds[] = $game_save->owner_id; // Keep the owner always
            if (count($userIds) > 0) {
                $placeholders = implode(',', array_fill(0, count($userIds), '?'));
                $query = "DELETE FROM users_has_game_saves WHERE game_saves_id = ? AND accepted = ?
                                       AND users_id NOT IN ($placeholders)";
                $params = [$game_save_id, $accepted];
                $params = array_merge($params, $userIds);
                $database->query($query, $params);
            }
            $database->commit();
        } catch (Exception $e) {
            $database->rollBack();
            throw $e;
        }

    }


    /**
     * @param int $user_id
     * @param int $game_save_id
     * @return null
     * @throws ErrorException
     */
    public static function removeUserFromSaveGame(int $user_id, int $game_save_id) {
        Database::delete("users_has_game_saves", ['users_id' => $user_id, 'game_saves_id' => $game_save_id]);
    }

    /**
     * @param int $game_save_id
     * @param int $biomassBurner
     * @param int $coalGenerator
     * @param int $fuelGenerator
     * @param int $nuclearReactor
     * @param int $power
     * @return null
     */
    public static function updatePowerProduction(int $game_save_id, int $power) {
        return Database::update("game_saves", ['total_power_production'], [$power], ['id' => $game_save_id]);
    }

    /**
     * @param int $game_save_id
     * @param int $owner_id
     * @param string $title
     * @param array $image
     * @param array $allowedUsers
     * @return bool
     * @throws ErrorException
     */
    public static function updateSaveGame(int $game_save_id, int $owner_id, string $title, array $image) {
        if ($owner_id != $_SESSION['userId']) {
            return false;
        }
        if ($image['tmp_name'] != '') {
            self::uploadImage($game_save_id, $image);
        }
        Database::update("game_saves", ['title'], [$title], ['id' => $game_save_id]);
        return true;
    }

    /**
     * @param int $game_save_id
     * @return object (success: bool, message: string)
     */
    public static function deleteSaveGame(int $game_save_id, $ownerId = null): object {
        if (!self::checkAccessOwner($game_save_id)) {
            return (object)['success' => false, 'message' => 'You are not the owner of this save game'];
        }
        $database = new NewDatabase();
        $database->beginTransaction();
        try {
            self::deleteImage($game_save_id);
            DedicatedServer::deleteServer($game_save_id, $database);
            ProductionLineSettings::deleteProductionLineSettings($game_save_id, $database);
            ProductionLines::deleteProductionLineOnGameId($game_save_id, $database);
            $database->delete("users_has_game_saves", ['game_saves_id' => $game_save_id]);
            $database->delete("game_saves", ['id' => $game_save_id]);

            if (self::getSaveGameById($game_save_id, $database)) {
                throw new Exception("The save game with id $game_save_id failed to be deleted by user {$_SESSION['userId']}");
            }

            $database->commit();


            return (object)['success' => true];
        } catch (Exception $e) {
            $database->rollBack();

            global $site;
            // Log the error message if needed
            error_log($e->getMessage());
            // if debug mode is on show the real error
            if ($site['debug']) {
                return (object)['success' => false, 'message' => $e->getMessage()];
            }
            return (object)['success' => false, 'message' => "Something went wrong. Please contact the administrator"];
        }

    }

    /**
     * @param int $game_save_id
     * @param array $image
     * @return bool
     */
    private static function uploadImage(int $game_save_id, array $image) {
        self::deleteImage($game_save_id);
        $name = 'save_game/' . $game_save_id . '/' . uniqid() . '.' . pathinfo($image['name'], PATHINFO_EXTENSION);
        $imagePath = 'image/' . $name;
        // check if the directory exists
        if (!file_exists('../public_html/image/save_game/' . $game_save_id)) {
            mkdir('../public_html/image/save_game/' . $game_save_id, 0777, true);
        }
        if (!move_uploaded_file($image['tmp_name'], $imagePath)) {
            return false;
        }
        Database::update("game_saves", ['image'], [$name], ['id' => $game_save_id]);
        return true;
    }

    /**
     * @param int $game_save_id
     * @return void
     */
    private static function deleteImage(int $game_save_id) {
        $gameSave = self::getSaveGameById($game_save_id);
        if ($gameSave->image != 'default_img.png' && file_exists('../public/image/' . $gameSave->image) && $gameSave->image != '' && $gameSave->image != null) {
            unlink('../public/image/' . $gameSave->image);
        }
    }

    /**
     * @param int $gameSaveId
     * @return array
     */
    public static function getAllowedUsers(int $gameSaveId): array {
        return Database::getAll("users_has_game_saves", ['users_id as id', 'username', 'role_id'], ['users' => 'users.id = users_has_game_saves.users_id'], ['game_saves_id' => $gameSaveId, 'accepted' => 1]);
    }

    /**
     * @param int $gameSaveId
     * @return array
     */
    public static function getRequestedUsers(int $gameSaveId): array {
        return Database::getAll("users_has_game_saves", ['users_id as id', 'username', 'role_id'], ['users' => 'users.id = users_has_game_saves.users_id'], ['game_saves_id' => $gameSaveId, 'accepted' => 0]);
    }

    /**
     * @param int $userId
     * @return void
     */
    public static function transferSaveGames(int $userId) {
        $gamesaves = self::getSaveGameByOwner($userId);
        foreach ($gamesaves as $game) {
            if ($game->owner_id !== $userId) {
                continue;
            }
            $gamesaveUsers = self::getAllowedUsers($game->id);
            $gamesaveUsers = array_values(array_filter($gamesaveUsers, function ($user) use ($userId) {
                return $user->users_id !== $userId;
            }));
            if (count($gamesaveUsers) == 0) {
                self::deleteSaveGame($game->id, $userId);
                continue;
            }
            $newOwner = $gamesaveUsers[0];
            Database::update("game_saves", ['owner_id'], [$newOwner->users_id], ['id' => $game->id]);

        }

    }

    /**
     * @param int $userId
     * @return void
     */
    public static function deleteUserHasGameSavesByUser(int $userId) {
        Database::delete("users_has_game_saves", ['users_id' => $userId]);
    }

    /**
     * @param int $userId
     * @return array
     */
    public static function getRequests(int $userId): array {
        return Database::getAll("users_has_game_saves", ['users_has_game_saves.id', 'game_saves_id', 'title', 'users.username'], ['game_saves' => 'game_saves.id = users_has_game_saves.game_saves_id', 'users' => 'users.id = game_saves.owner_id'], ['users_id' => $userId, 'accepted' => 0]);
    }

    /**
     * @param int $requestId
     * @return void
     */
    public static function declineRequest(int $requestId) {
        Database::delete("users_has_game_saves", ['id' => $requestId]);
    }

    /**
     * @param int $requestId
     * @return void
     */
    public static function acceptRequest(int $requestId) {
        Database::update("users_has_game_saves", ['accepted'], [1], ['id' => $requestId]);
    }

    /**
     * @param int $gameSaveId
     * @return void
     */
    public static function setLastVisitedSaveGame(int $gameSaveId): void {
        $_SESSION['lastVisitedSaveGame'] = $gameSaveId;
        Database::update("users_has_game_saves", ['last_visited'], [0], ['users_id' => $_SESSION['userId']]);
        Database::update("users_has_game_saves", ['last_visited'], [1], ['game_saves_id' => $gameSaveId, 'users_id' => $_SESSION['userId']]);
    }

    public static function getLastVisitedSaveGame() {
        $lastVisited = Database::get("users_has_game_saves", ['game_saves_id'], ['users' => 'users.id = users_has_game_saves.users_id'], ['users_id' => $_SESSION['userId'], 'last_visited' => 1]);
        if ($lastVisited) {
            return $lastVisited->game_saves_id;
        } else {
            return null;
        }
    }

    public static function changeCardView(int $gameSaveId, int $cardView): void {
        Database::update("users_has_game_saves", ['card_view'], [$cardView], ['game_saves_id' => $gameSaveId, 'users_id' => $_SESSION['userId']]);
    }

    /**
     * Checks if the user is the owner of the game save
     *
     * @param int $gameSaveId The id of the game save
     * @return bool True if the user is the owner of the game save, false otherwise
     */
    public static function checkAccessOwner(int $gameSaveId): bool {
        $gameSave = self::getSaveGameById($gameSaveId);
        if ($gameSave->role !== Role::OWNER->value && $gameSave->accepted != 1) {
            return false;
        }
        return true;

    }

    /**
     * Checks if an user has access to a game save
     *
     * @param $gameSaveId int The id of the game save
     * @return bool True if the user has access to the game save, false otherwise
     */
    public static function checkAccessUser(int $gameSaveId): bool {
        $userId = $_SESSION['userId'];
        $gameSave = Database::get("users_has_game_saves", ['game_saves_id'], [], ['game_saves_id' => $gameSaveId, 'users_id' => $userId, 'accepted' => 1]);
        if ($gameSave) {
            return true;
        }

        return false;
    }

    /**
     * Checks if an user has access to a game save
     *
     * @param $id int The id of the game save
     * @return mixed
     */
    public static function getSaveGameShares(int $id): mixed {
        return Database::getAll("users_has_game_saves", ['users_id', 'username'], ['users' => 'users.id = users_has_game_saves.users_id'], ['game_saves_id' => $id, 'accepted' => 1]);
    }

    public static function HideGameSave(mixed $requestId, mixed $userId) {
        Database::update("users_has_game_saves", ['hidden'], [1], ['game_saves_id' => $requestId, 'users_id' => $userId]);
    }

    public static function UnhideGameSave(mixed $requestId, mixed $userId) {
        Database::update("users_has_game_saves", ['hidden'], [0], ['game_saves_id' => $requestId, 'users_id' => $userId]);
    }
}