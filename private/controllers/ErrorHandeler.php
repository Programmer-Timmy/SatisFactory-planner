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

    public static function getAll404Logs($limit = 10, $ip = null, $page = null) {
        $where = [];
        if ($ip) {
            $where[] = "ip_address = '$ip'";
        }
        if ($page) {
            $where[] = "requested_url = '$page'";
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return Database::query("SELECT users.username, error_404_logs.requested_url, error_404_logs.ip_address, error_404_logs.referrer_url, error_404_logs.error_timestamp FROM error_404_logs LEFT JOIN users ON error_404_logs.users_id = users.id $whereClause order by error_timestamp desc limit $limit");
    }

    public static function getAll403Logs($limit = 10, $ip = null, $page = null) {
        $where = [];
        if ($ip) {
            $where[] = "ip_address = '$ip'";
        }
        if ($page) {
            $where[] = "requested_url = '$page'";
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return Database::query("SELECT users.username, error_403_logs.requested_url, error_403_logs.ip_address, error_403_logs.referrer_url, error_403_logs.error_timestamp FROM error_403_logs LEFT JOIN users ON error_403_logs.users_id = users.id $whereClause order by error_timestamp desc limit $limit");
    }

    public static function getYearlyLogs($year): array {
        $fourOFourLogs = Database::query("SELECT users.username, error_404_logs.requested_url, error_404_logs.ip_address, error_404_logs.referrer_url, error_404_logs.error_timestamp FROM error_404_logs LEFT JOIN users ON error_404_logs.users_id = users.id WHERE YEAR(error_404_logs.error_timestamp) = ? order by error_timestamp desc", [$year]);
        $threeOFourLogs = Database::query("SELECT users.username, error_403_logs.requested_url, error_403_logs.ip_address, error_403_logs.referrer_url, error_403_logs.error_timestamp FROM error_403_logs LEFT JOIN users ON error_403_logs.users_id = users.id WHERE YEAR(error_403_logs.error_timestamp) = ? order by error_timestamp desc", [$year]);
        foreach ($fourOFourLogs as $fourOFourLog) {
            $fourOFourLog->error_timestamp = GlobalUtility::dateTimeToLocal($fourOFourLog->error_timestamp);
        }

        foreach ($threeOFourLogs as $threeOFourLog) {
            $threeOFourLog->error_timestamp = GlobalUtility::dateTimeToLocal($threeOFourLog->error_timestamp);
        }
        return [$fourOFourLogs, $threeOFourLogs];
    }

    public static function searchErrorLogs($ip, $url, $type) {
        $where = [];
        if ($ip) {
            $where[] = "ip_address = '$ip'";
        }
        if ($url) {
            $where[] = "requested_url = '$url'";
        }

        if ($type === '403') {
            return Database::query("SELECT users.username, error_403_logs.requested_url, error_403_logs.ip_address, error_403_logs.referrer_url, error_403_logs.error_timestamp FROM error_403_logs LEFT JOIN users ON error_403_logs.users_id = users.id " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . " order by error_timestamp desc");
        } else if ($type === '404') {
            return Database::query("SELECT users.username, error_404_logs.requested_url, error_404_logs.ip_address, error_404_logs.referrer_url, error_404_logs.error_timestamp FROM error_404_logs LEFT JOIN users ON error_404_logs.users_id = users.id " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . " order by error_timestamp desc");
        } else {
            return json_encode("{
                'error' => 'Invalid type provided must be one of the following: all, 404, 403'
            }");
        }
    }

    public static function get404LogsByMonth($month, $year) {
        return Database::query("SELECT users.username, error_404_logs.requested_url, error_404_logs.ip_address, error_404_logs.referrer_url, error_404_logs.error_timestamp FROM error_404_logs LEFT JOIN users ON error_404_logs.users_id = users.id WHERE MONTH(error_404_logs.error_timestamp) = ? AND YEAR(error_404_logs.error_timestamp) = ? order by error_timestamp desc", [$month, $year]);
    }

    public static function get403LogsByMonth($month, $year) {
        return Database::query("SELECT users.username, error_403_logs.requested_url, error_403_logs.ip_address, error_403_logs.referrer_url, error_403_logs.error_timestamp FROM error_403_logs LEFT JOIN users ON error_403_logs.users_id = users.id WHERE MONTH(error_403_logs.error_timestamp) = ? AND YEAR(error_403_logs.error_timestamp) = ? order by error_timestamp desc", [$month, $year]);
    }

    public static function getAvailableYears() {
        return Database::query("SELECT DISTINCT YEAR(error_timestamp) as year FROM error_404_logs UNION SELECT DISTINCT YEAR(error_timestamp) as year FROM error_403_logs");
    }

    public static function getTopTen404Logs(): false|array {
        $database = new NewDatabase();
        return $database->query("SELECT requested_url, COUNT(requested_url) as count FROM error_404_logs GROUP BY requested_url ORDER BY count DESC LIMIT 10");
    }

    public static function getTopTen403Logs(): false|array {
        $database = new NewDatabase();
        return $database->query("SELECT requested_url, COUNT(requested_url) as count FROM error_403_logs GROUP BY requested_url ORDER BY count DESC LIMIT 10");
    }

    public static function getAvailable404IpAddresses() {
        return Database::query("
        SELECT ip_address, COUNT(*) as count 
        FROM (
            SELECT ip_address FROM error_404_logs
        ) as combined
        GROUP BY ip_address
    ");
    }

    public static function getAvailable403IpAddresses() {
        return Database::query("
        SELECT ip_address, COUNT(*) as count 
        FROM (
            SELECT ip_address FROM error_403_logs
        ) as combined
        GROUP BY ip_address
    ");
    }

    public static function getAvailable403Pages() {
        return Database::query("
        SELECT requested_url, COUNT(*) as count 
        FROM (
            SELECT requested_url FROM error_403_logs
        ) as combined
        GROUP BY requested_url
    ");
    }

    public static function getAvailable404Pages() {
        return Database::query("
        SELECT requested_url, COUNT(*) as count 
        FROM (
            SELECT requested_url FROM error_404_logs
        ) as combined
        GROUP BY requested_url
    ");
    }

    /**
     * Blocks an IP address if it has made more than 5 404 errors in the last minute
     *
     * @param string $ip The IP address to block
     * @return bool True if the IP was blocked, false otherwise
     * @throws ErrorException
     */
    public static function blockIPForRapid404Errors(string $ip) {
        $MAX_404_ERRORS = 5;

        $time = date('Y-m-d H:i:s', time() - 5);
        $count = Database::query("SELECT COUNT(*) as count FROM error_404_logs WHERE ip_address = ? AND error_timestamp > ?", [$ip, $time])[0]->count;
        if ($count >= $MAX_404_ERRORS && !AuthControler::isIPBlocked($ip)) {
            AuthControler::blockIP($ip, 'Rapid 404 Errors');
            return true;

        }
        return false;
    }

    public static function getUserErrorLogs($userId) {
        return Database::query("SELECT * FROM error_404_logs WHERE users_id = ? UNION SELECT * FROM error_403_logs WHERE users_id = ?", [$userId, $userId]);
    }


}