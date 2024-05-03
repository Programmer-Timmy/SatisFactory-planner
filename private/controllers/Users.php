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

    public static function updateUsername($id, $username)
    {
        Database::update("users", ['username'], [$username], ['id' => $id]);
        return true;
    }

    public static function updatePassword($id, $password)
    {
        $password = password_hash($password, PASSWORD_DEFAULT);
        Database::update("users", ['password_hash'], [$password], ['id' => $id]);
        return true;
    }

    public static function createUser($username, $password)
    {
        $password = password_hash($password, PASSWORD_DEFAULT);
        return Database::insert("users", ['username', 'password_hash'], [$username, $password]);
    }

    public static function deleteUser($id)
    {
        GameSaves::transferSaveGames($id);
        return Database::delete("users", ['id' => $id]);
    }



}