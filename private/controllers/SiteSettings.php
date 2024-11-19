<?php

class SiteSettings {

    public static function getDataVersion() {
        $database = new NewDatabase();
        $version = $database->get('site_settings', ['data_version'], where: ['id' => 1]);
        return $version->data_version;
    }

    public static function incrementDataVersion(): void {
        $database = new NewDatabase();
        $version = $database->get('site_settings', ['data_version', 'id']);
        $newVersion = $version->data_version + 1;
        $database->update('site_settings', ['data_version'], [$newVersion], ['id' => $version->id]);
    }

    public static function isOwner(): bool {
        $database = new NewDatabase();
        $owner = $database->get('site_settings', ['owner_id'], where: ['id' => 1]);
        return $owner->owner_id == $_SESSION['userId'];
    }

    public static function getSettings() {
        $database = new NewDatabase();
        return $database->get('site_settings', where: ['id' => 1]);

    }

    public static function updateSettings(array $data) {
        $database = new NewDatabase();
        $database->update('site_settings', array_keys($data), array_values($data), ['id' => 1]);
    }

}