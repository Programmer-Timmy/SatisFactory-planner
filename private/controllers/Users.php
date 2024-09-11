<?php

class Users
{
    public static function getAllUsers()
    {
        return Database::getAll("users");
    }

    public static function getUserById($id)
    {
        return Database::get("users", ['*'], [], ["id" => $id]);
    }

    public static function getUserByUsername($username)
    {
        return Database::get("users", ['*'], [], ["username" => $username]);
    }

    public static function getUserByEmail($email)
    {
        return Database::get("users", ['*'], [], ["email" => $email]);
    }

    public static function updateUsername($id, $username, $email, $updates)
    {
        Database::update("users", ['username', 'email', 'updates'], [$username, $email, $updates], ['id' => $id]);
        return true;
    }

    public static function updatePassword($id, $password)
    {
        $password = password_hash($password, PASSWORD_DEFAULT);
        Database::update("users", ['password_hash'], [$password], ['id' => $id]);
        return true;
    }

    public static function createUser($username, $password, $email)
    {
        $password = password_hash($password, PASSWORD_DEFAULT);
        return Database::insert("users", ['username', 'password_hash', 'email'], [$username, $password, $email]);
    }

    public static function deleteUser($id)
    {
        ProductionLineSettings::deleteProductionLineSettingsByUser($id);
        GameSaves::transferSaveGames($id);
        GameSaves::deleteUserHasGameSavesByUser($id);

        return Database::delete("users", ['id' => $id]);
    }



}