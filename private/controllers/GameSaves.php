<?php

class GameSaves
{
    /**
     * @param int $owner_id
     * @return mixed
     */
    public static function getSaveGameByOwner(int $owner_id)
    {
        return Database::getAll("game_saves", ['*'], [], ['owner_id' => $owner_id]);
    }

    /**
     * @param int $user_id
     * @return mixed
     */
    public static function  getSaveGamesByUser(int $user_id)

    {
        return Database::getAll("users_has_game_saves", ['game_saves_id', 'title', 'created_at', 'username as Owner', 'image', 'game_saves.id', 'game_saves.owner_id'], ['game_saves' => 'game_saves.id = users_has_game_saves.game_saves_id', 'users' => 'users.id = game_saves.owner_id'], ['users_has_game_saves.users_id' => $user_id]);
    }

    /**
     * @param int $id
     * @return mixed
     */
    public static function getSaveGameById(int $id)
    {
        return Database::get("users_has_game_saves", ['game_saves.*', 'users.username'], ['game_saves' => 'game_saves.id = users_has_game_saves.game_saves_id', "users" => "game_saves.owner_id = users.id"] ,['game_saves.id' => $id]);
    }

    /**
     * @param int $user_id
     * @param string $title
     * @param array $image
     * @param array $allowedUsers
     * @return null
     * @throws ErrorException
     */
    public static function createSaveGame(int $user_id, string $title, array $image, array $allowedUsers)
    {
        $createdAt = date('Y-m-d H:i:s');
        $id = Database::insert("game_saves", ['owner_id', 'title', 'created_at'], [$user_id, $title, $createdAt]);
        Database::insert("users_has_game_saves", ['users_id', 'game_saves_id'], [$user_id, $id]);

        if ($image['tmp_name'] != '') {
            self::uploadImage($id, $image);
        }

        foreach ($allowedUsers as $user) {
            self::addUserToSaveGame($user, $id);
        }

        return $id;
    }

    /**
     * @param int $user_id
     * @param int $game_save_id
     * @return null
     * @throws ErrorException
     */
    public static function addUserToSaveGame(int $user_id, int $game_save_id)
    {

        return Database::insert("users_has_game_saves", ['users_id', 'game_saves_id'], [$user_id, $game_save_id]);

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
    public static function updatePowerProduction(int $game_save_id, int $biomassBurner, int $coalGenerator, int $fuelGenerator, int $nuclearReactor, int $alienPowerAugmenter, int $power)
    {
        return Database::update("game_saves", ['biomass_burner', '`coal-powered_generator`', '`fuel-powered_generator`', 'nuclear_power_plant', 'total_power_production', 'alien_power_augmenter'], [$biomassBurner, $coalGenerator, $fuelGenerator, $nuclearReactor, $power, $alienPowerAugmenter], ['id' => $game_save_id]);
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
    public static function updateSaveGame(int $game_save_id, int $owner_id, string $title, array $image, array $allowedUsers)
    {
        if ($owner_id != $_SESSION['userId']) {
            return false;
        }
        if ($image['tmp_name'] != '') {
            self::uploadImage($game_save_id, $image);
        }
        Database::update("game_saves", ['title'], [$title], ['id' => $game_save_id]);
        Database::query("DELETE FROM users_has_game_saves WHERE game_saves_id = ? and users_id != ?", [$game_save_id, $_SESSION['userId']]);
        foreach ($allowedUsers as $user) {
            self::addUserToSaveGame($user, $game_save_id);
        }
        return true;
    }

    /**
     * @param int $game_save_id
     * @return false|void
     */
    public static function deleteSaveGame(int $game_save_id)
    {
        if (Database::get("game_saves", ['id'], [], ['id' => $game_save_id, 'owner_id' => $_SESSION['userId']]) == false) {
            return false;
        }
        self::deleteImage($game_save_id);
        ProductionLineSettings::deleteProductionLineSettings($game_save_id);
        ProductionLines::deleteProductionLineOnGameId($game_save_id);
        Database::delete("users_has_game_saves", ['game_saves_id' => $game_save_id]);
        Database::delete("game_saves", ['id' => $game_save_id, 'owner_id' => $_SESSION['userId']]);

    }

    /**
     * @param int $game_save_id
     * @param array $image
     * @return false|null
     */
    private static function uploadImage(int $game_save_id, array $image)
    {
        self::deleteImage($game_save_id);
        $imagePath = 'image/' . $game_save_id . '.' . pathinfo($image['name'], PATHINFO_EXTENSION);
        if (!move_uploaded_file($image['tmp_name'], $imagePath)) {
            return false;
        }
        return Database::update("game_saves", ['image'], [$game_save_id . '.' . pathinfo($image['name'], PATHINFO_EXTENSION)], ['id' => $game_save_id]);
    }

    /**
     * @param int $game_save_id
     * @return void
     */
    private static function deleteImage(int $game_save_id)
    {
        $gameSave = self::getSaveGameById($game_save_id);
        if ($gameSave->image != 'default_img.png' && file_exists('../public/image/' . $gameSave->image) && $gameSave->image != '' && $gameSave->image != null) {
            unlink('/image/' . $gameSave->image);
        }
    }

    /**
     * @param int $gameSaveId
     * @return array
     */
    public static function getAllowedUsers(int $gameSaveId): array
    {
        $allowedUsers = Database::getAll("users_has_game_saves", ['users_id'], [], ['game_saves_id' => $gameSaveId]);
        $users = [];
        foreach ($allowedUsers as $user) {
            $users[] = $user->users_id;
        }
        return $users;
    }

    /**
     * @param int $userId
     * @return void
     */
    public static function transferSaveGames(int $userId)
    {
        $gamesaves = self::getSaveGamesByUser($userId);
        foreach ($gamesaves as $game) {
            if ($game->owner_id == $userId) {
                continue;
            }
            $gamesaveUsers = self::getAllowedUsers($game->id);
            $gamesaveUsers = array_diff($gamesaveUsers, [$userId]);
            if (count($gamesaveUsers) == 0) {
                self::deleteSaveGame($game->id);
                continue;
            }
            $newOwner = $gamesaveUsers[0];
            Database::update("game_saves", ['owner_id'], [$newOwner], ['id' => $game->id]);

        }

    }


}