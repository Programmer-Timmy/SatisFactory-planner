<?php

class SiteSettings {

    public static function getDataVersion() {
        $database = new NewDatabase();
        $version = $database->get('site_settings', ['data_version']);
        return $version->data_version;
    }

    public static function incrementDataVersion() {
        $database = new NewDatabase();
        $version = $database->get('site_settings', ['data_version', 'id']);
        $newVersion = $version->data_version + 1;
        $database->update('site_settings', ['data_version'], [$newVersion], ['id' => $version->id]);
    }

}