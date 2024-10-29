<?php

class ErrorHandeler {

    public static function add404Log($requestedUrl, $referer = null, $userId = null) {
        $timeStamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'];
        Database::insert('error_404_logs', ['requested_url', 'ip_address', 'referrer_url', 'users_id', 'error_timestamp'], [$requestedUrl, $ip, $referer, $userId, $timeStamp]);

    }

    public static function add403Log($requestedUrl, $referer = null, $userId = null) {
        $timeStamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'];
        Database::insert('error_403_logs', ['requested_url', 'ip_address', 'referrer_url', 'users_id', 'error_timestamp'], [$requestedUrl, $ip, $referer, $userId, $timeStamp]);

    }

    public static function getAll404Logs() {
        // user can be null
        return Database::query("SELECT users.username, error_404_logs.requested_url, error_404_logs.ip_address, error_404_logs.referrer_url, error_404_logs.error_timestamp FROM error_404_logs LEFT JOIN users ON error_404_logs.users_id = users.id");
    }

    public static function getAll403Logs() {
        // user can be null
        return Database::query("SELECT users.username, error_403_logs.requested_url, error_403_logs.ip_address, error_403_logs.referrer_url, error_403_logs.error_timestamp FROM error_403_logs LEFT JOIN users ON error_403_logs.users_id = users.id");
    }

}