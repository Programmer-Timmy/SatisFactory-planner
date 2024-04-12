<?php

class GameSaves
{

    public static function getSaveGamesByUser(int $user_id)

    {
        return Database::getAll("users_has_game_saves", ['game_saves_id', 'title', 'created_at', 'username as Owner'], ['game_saves' => 'game_saves.id = users_has_game_saves.game_saves_id', 'users' => 'users.id = game_saves.owner_id'], ['users_has_game_saves.users_id' => $user_id]);
    }

    public static function getSaveGameById(int $id)
    {
        return Database::get("users_has_game_saves", ['game_saves.*', 'users.username'], ['game_saves' => 'game_saves.id = users_has_game_saves.game_saves_id', "users" => "game_saves.owner_id = users.id"] ,['game_saves.id' => $id]);
    }

    public static function createSaveGame(int $user_id, string $title)
    {
        $createdAt = date('Y-m-d H:i:s');
        $id = Database::insert("game_saves", ['owner_id', 'title', 'created_at'], [$user_id, $title, $createdAt]);
        Database::insert("users_has_game_saves", ['users_id', 'game_saves_id'], [$user_id, $id]);
        return $id;
    }

    public static function addUserToSaveGame(int $user_id, int $game_save_id)
    {
        return Database::insert("users_has_game_saves", ['users_id', 'game_saves_id'], [$user_id, $game_save_id]);
    }

    public static function updatePowerProduction(int $game_save_id, int $biomassBurner, int $coalGenerator, int $fuelGenerator, int $nuclearReactor, int $power)
    {
        return Database::update("game_saves", ['biomass_burner', 'coal_generator', 'fuel_generator', 'nuclear_power_plant', 'total_power_production'], [$biomassBurner, $coalGenerator, $fuelGenerator, $nuclearReactor, $power], ['id' => $game_save_id]);
    }

}