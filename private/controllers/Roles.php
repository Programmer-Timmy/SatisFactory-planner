<?php

class Roles {

    public static function getAllRoles() {
        return Database::getAll(table: "roles", orderBy: "role_order ASC");
    }

    public static function getAllRolesWithPermissions() {
        $sql = "
    SELECT
        r.id,
        r.name,
        r.description,
        IFNULL(rp.permissions, JSON_ARRAY()) AS permissions
    FROM roles r
    LEFT JOIN (
        SELECT
            rp.role_id,
            JSON_ARRAYAGG(JSON_OBJECT(
                'id', p.id,
                'name', p.name,
                'description', p.description
            )) AS permissions
        FROM role_permission rp
        INNER JOIN permissions p ON rp.permission_id = p.id
        GROUP BY rp.role_id
    ) rp ON r.id = rp.role_id
    ORDER BY r.role_order ASC
";
        $database = new NewDatabase();
        $results = $database->query($sql);
        foreach ($results as $role) {
            $role->permissions = json_decode($role->permissions) ?: [];
        }
        return $results;


    }
}