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


}