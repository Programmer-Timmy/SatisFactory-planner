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
        if (password_verify($password, $user->password_hash)) {
            if ($user->verified != 1) {
                return [$user->username, 'notVerified'];
                exit();
            }
            $_SESSION[$site['accounts']['sessionName']] = $user->id;
            if ($site['admin']['enabled']) {
                if ($user->admin = 1) {
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
            return null;
            exit();
        }
    }
}