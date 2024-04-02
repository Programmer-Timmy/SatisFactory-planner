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
            $_SESSION[$site['accounts']['sessionName']] = $user->id;
            if ($site['admin']['enabled']) {
                if ($user->admin = 1) {
                    $_SESSION[$site['admin']['sessionName']] = $user->id;
                }
            }
            if ($site['saveUrl']) {
                return $_SESSION['redirect'];
                exit();
            }
            return 'home';
            exit();
        } else {
            return null;
            exit();
        }
    }
}