<?php

class AuthControler
{
    public static function login(string $username, string $password)
    {
        session_regenerate_id(true); // Prevent session fixation attacks

        global $site;
        $user = Database::get($site['user-adminTable'], ['*'], [], ['username' => $username]);
        if (!$user) {
            return null;
            exit();
        }
        if (self::checkIfmaxLoginAttempts($user->id, $_SERVER['REMOTE_ADDR'])) {
            return [$user->username, 'maxAttempts'];
            exit();
        }

        if (password_verify($password, $user->password_hash)) {
            self::setLoginAttempt($user->id, true);
            if ($user->verified != 1) {
                return [$user->username, 'notVerified'];
                exit();
            }

            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            $_SESSION[$site['accounts']['sessionName']] = $user->id;
            if ($site['admin']['enabled']) {
                if ($user->admin == 1) {
                    $_SESSION[$site['admin']['sessionName']] = $user->id;
                }
            }

            if ($user->email == null) {
                return '/account';
                exit();
            }

            if ($site['saveUrl']) {

                if (isset($_SESSION['redirect'])) {

                    $_SESSION['lastVisitedSaveGame'] = GameSaves::getSaveGamesByUser($user->id)[0]->id;
                    return str_contains($_SESSION['redirect'], '/login') ? '/game_saves' : $_SESSION['redirect'];
                    exit();
                }else{
                    return '/game_saves';
                    exit();
                }
            }
            return 'game_saves';
            exit();
        } else {
            self::setLoginAttempt($user->id, false);
            return null;
            exit();

        }
    }

    public static function loginGoogleSSO($googleId){
        global $site;
        $user = Users::getGoogleConnectedUser($googleId);
        if (!$user) {
            return;
            exit();
        }

        if ($user->verified != 1) {
            return [$user->username, 'notVerified'];
            exit();
        }

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['accounts'] = $user->id;

        if ($user->email == null) {
            return '/account';
            exit();
        }

        $_SESSION[$site['accounts']['sessionName']] = $user->id;
        if ($site['admin']['enabled']) {
            if ($user->admin == 1) {
                $_SESSION[$site['admin']['sessionName']] = $user->id;
            }
        }

        if ($site['saveUrl']) {
            if (isset($_SESSION['redirect'])) {
                $_SESSION['lastVisitedSaveGame'] = GameSaves::getSaveGamesByUser($user->id)[0]->id;
                return str_contains($_SESSION['redirect'], '/login') ? '/game_saves' : $_SESSION['redirect'];
                exit();
            }else{
                return '/game_saves';
                exit();
            }
        }
        return 'game_saves';
        exit();


    }

    public static function getAllLoginAttempts($year = null, $limit = null) {
        if ($limit) {
            $limit = "LIMIT $limit";
        } else {
            $limit = '';
        }

        if ($year) {
            return Database::query("SELECT users.username, login_attempts.ip_address, login_attempts.success, login_attempts.login_timestamp FROM login_attempts LEFT JOIN users ON login_attempts.users_id = users.id WHERE YEAR(login_timestamp) = ? $limit", [$year]);
        }
        return Database::getAll('login_attempts', columns: ['username', 'ip_address', 'success', 'login_timestamp'], join: ['users' => 'users_id = users.id'], orderBy: 'login_timestamp DESC ' . $limit);
    }

    public static function getSuccessfulLoginAttempts(string | null $year = null) {
        if ($year) {
            return Database::query("SELECT users.username, login_attempts.ip_address, login_attempts.success, login_attempts.login_timestamp FROM login_attempts LEFT JOIN users ON login_attempts.users_id = users.id WHERE success = 1 AND YEAR(login_timestamp) = ?", [$year]);
        }

        return Database::getAll('login_attempts', columns: ['username', 'ip_address', 'success', 'login_timestamp'], join: ['users' => 'users_id = users.id'], where: ['success' => 1]);
    }

    public static function getFailedLoginAttempts(string | null $year = null) {
        if ($year) {
            return Database::query("SELECT users.username, login_attempts.ip_address, login_attempts.success, login_attempts.login_timestamp FROM login_attempts LEFT JOIN users ON login_attempts.users_id = users.id WHERE success = 0 AND YEAR(login_timestamp) = ?", [$year]);
        }

        return Database::getAll('login_attempts', columns: ['username', 'ip_address', 'success', 'login_timestamp'], join: ['users' => 'users_id = users.id'], where: ['success' => 0]);
    }

    public static function searchLoginAttempts($ip, $userId, $year, $success) {
        $where = [];
        if ($ip) {
            $where[] = "ip_address = '$ip'";
        }
        if ($userId) {
            $where[] = "users_id = '$userId'";
        }
        if ($year) {
            $where[] = "YEAR(login_timestamp) = '$year'";
        }
        if ($success !== null && $success !== '') {
            $where[] = "success = '$success'";
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return Database::query("SELECT users.username, login_attempts.ip_address, login_attempts.success, login_attempts.login_timestamp FROM login_attempts LEFT JOIN users ON login_attempts.users_id = users.id $whereClause");

    }

    private static function setLoginAttempt($userId, $success) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $timeStamp = date('Y-m-d H:i:s');
        $success = $success ? 1 : 0;
        return Database::insert('login_attempts', ['users_id', 'ip_address', 'login_timestamp', 'success'], [$userId, $ip, $timeStamp, $success]);
    }

    private static function checkIfmaxLoginAttempts($userId, $ip) {
        $time = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $loginAttempts = Database::query("SELECT COUNT(*) as count FROM login_attempts WHERE users_id = ? AND ip_address = ? AND success = 0 AND login_timestamp > ?", [$userId, $ip, $time]);
        if ($loginAttempts[0]->count >= 5) {
            return true;
        }
        return false;
    }

    public static function getBlockedLoginAttempts($year) {
        $attempts = self::getAllLoginAttempts($year);
        $blockedIps = [];

        foreach ($attempts as $attempt) {
            if ($attempt->success == 0) {
                $timeWindowStart = date('Y-m-d H:i:s', strtotime($attempt->login_timestamp . ' -1 hour'));

                $loginAttempts = Database::query(
                    "SELECT COUNT(*) as count 
                 FROM login_attempts 
                 WHERE ip_address = ? 
                 AND success = 0 
                 AND login_timestamp > ?",
                    [$attempt->ip_address, $timeWindowStart]
                );

                if ($loginAttempts[0]->count >= 5) {
                    // Check if this IP has been recorded as blocked already
                    $isBlocked = false;
                    // if already blocked, skip
                    foreach ($blockedIps as $blocked) {
                        if ($blocked->ip_address === $attempt->ip_address && $blocked->blocked_until > $timeWindowStart) {
                            $isBlocked = true;
                            break;
                        }
                    }

                    if (!$isBlocked) {
                        // Create an object with the desired structure
                        $blockedIps[] = (object)[
                            'ip_address' => $attempt->ip_address,
                            'blocked_until' => $attempt->login_timestamp
                        ];
                    }
                }
            }
        }
        return $blockedIps;
    }

    /**
     * Check if an IP is blocked
     * @param string $ip IP address to check
     * @return bool True if the IP is blocked, false otherwise
     */
    public static function isIPBlocked($ip) {
        $blockedState = Database::query("SELECT COUNT(*) as count FROM blocked_ips WHERE ip_address = ? AND blocked_until > ?", [$ip, date('Y-m-d H:i:s')]);

        return $blockedState[0]->count > 0;
    }

    /**
     * Block an IP address
     * @param string $ip IP address to block
     * @param string $reason Reason for blocking
     * @param int $time Time in hours to block the IP
     *
     * @return string ID of the newly created blocked IP
     * @throws ErrorException
     */
    public static function blockIP($ip, $reason, $time = 1) {
        $time = date('Y-m-d H:i:s', strtotime("+$time hours"));
        return Database::insert('blocked_ips', ['ip_address', 'blocked_until', 'reason'], [$ip, $time, $reason]);
    }

    public static function getLoginAttempts($userId) {
        return Database::getAll(table: 'login_attempts', where: ['users_id' => $userId] , orderBy: 'login_timestamp DESC');
    }

    public static function validateAdminToken($headers) {
        $dotenv = parse_ini_file(__DIR__ . '/../../.env');
        $ADMIN_TOKEN = $dotenv['ADMIN_API_KEY'];

        $authHeader = $headers['Authorization'] ?? '';

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            http_response_code(403);
            return['success' => false, 'error' => ['code' => 403, 'message' => 'No access (no token)']];
        }

        $token = substr($authHeader, 7);
        if ($token !== $ADMIN_TOKEN) {
            http_response_code(403);
            return['success' => false, 'error' => ['code' => 403, 'message' => 'No access (wrong token)']];
        }

        return ['success' => true];

    }

}