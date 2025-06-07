<?php

class Roles {

    public static function getAllRoles() {
        return Database::getAll("roles", ['*'], [], [], 'id ASC');
    }

    public static function getRoleById(int $id) {
        return Database::get("roles", ['*'], [], ['id' => $id]);
    }
}