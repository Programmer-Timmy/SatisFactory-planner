<?php

class Roles {

    public static function getAllRoles() {
        return Database::getAll("roles", ['*'], [], [], 'id ASC');
    }

}