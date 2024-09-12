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

    public static function updateUser($id, $username, $email, $updates): bool
    {
        Database::update("users", ['username', 'email', 'updates'], [$username, $email, $updates], ['id' => $id]);
        return true;
    }

    public static function updatePassword($id, $password): bool
    {
        $password = password_hash($password, PASSWORD_DEFAULT);
        Database::update("users", ['password_hash'], [$password], ['id' => $id]);
        return true;
    }

    public static function createUser($username, $password, $email)
    {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $verified_code = bin2hex(random_bytes(16));
        Mailer::sendVerificationEmail($email, $verified_code);
        return Database::insert("users", ['username', 'password_hash', 'email', 'verified'], [$username, $password, $email, $verified_code]);
    }

    public static function deleteUser($id)
    {
        ProductionLineSettings::deleteProductionLineSettingsByUser($id);
        GameSaves::transferSaveGames($id);
        GameSaves::deleteUserHasGameSavesByUser($id);

        return Database::delete("users", ['id' => $id]);
    }

    public static function verifyUser($code): bool
    {
        $user = Database::get("users", ['*'], [], ['verified' => $code]);
        if ($user) {
            Database::update("users", ['verified'], [1], ['id' => $user->id]);
            return true;
        }
        return false;
    }

    public static function resendVerificationEmail($username)
    {
        $user = Database::get("users", ['*'], [], ['username' => $username]);
        if ($user) {
            $verified_code = bin2hex(random_bytes(16));
            Database::update("users", ['verified'], [$verified_code], ['id' => $user->id]);
            Mailer::sendVerificationEmail($user->email, $verified_code);
            return true;
        }
        return false;
    }



}