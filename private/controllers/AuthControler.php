<?php

class AuthControler
{
    public static function login(string $username, string $password)
    {
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
                    return $_SESSION['redirect'];
                    exit();
                }else{
                    return '/home';
                    exit();
                }
            }
            return 'home';
            exit();
        } else {
            self::setLoginAttempt($user->id, false);
            return null;
            exit();

        }
    }

    public static function getAllLoginAttempts() {
        return Database::getAll('login_attempts', columns: ['username', 'ip_address', 'success', 'login_timestamp'], join: ['users' => 'users_id = users.id']);
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

}