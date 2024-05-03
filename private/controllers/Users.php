<?php

class Users
{
    public static function getAllUsers()
    {
        return Database::getAll("users");
    }



}