<?php

class Users
{
    public static function getAllUsers()
    {
        return Database::getAll(
            "users",
            [
                '*',
                '(SELECT COUNT(*) FROM game_saves WHERE owner_id = users.id) as saves',
                '(SELECT COUNT(*) FROM users_has_game_saves WHERE users_id = users.id) as shared_saves',
            ], [], ['verified' => 1]);
    }

    public static function getAllValidatedUsers()
    {
        return Database::getAll("users", ['*'], [], ['verified' => 1]);
    }

    /**
     * @param $search string
     * @return array
     * @note Only returns verified users
     * @note Only returns id and username for privacy and security reasons
     */
    public static function searchUsers($search)
    {
        return Database::query("SELECT id, username FROM users WHERE username LIKE ? and verified = 1", ["%$search%"]);
    }

    public static function getUserById($id)
    {
        return Database::get("users", ['*'], [], ["id" => $id]);
    }

    public static function getUserByUsername($username)
    {
        return Database::get("users", ['*'], [], ["username" => $username]);
    }

    public static function getUserByEmail($email)
    {
        return Database::get("users", ['*'], [], ["email" => $email]);
    }

    public static function updateUser($id, $username, $email, $updates): bool
    {
        Database::update("users", ['username', 'email', 'updates'], [$username, $email, $updates], ['id' => $id]);
        return true;
    }

    /**
     * @param $id
     * @param $username
     * @param $email
     * @param $updates
     * @param $admin
     * @param $verified
     * @return bool
     */
    public static function updateUserAdmin($id, $username, $email, $updates, $admin, $verified): bool {
        Database::update("users", ['username', 'email', 'updates', 'admin', 'verified'], [$username, $email, $updates, $admin, $verified], ['id' => $id]);
        return true;
    }

    public static function updatePassword($id, $password): bool
    {
        $password = password_hash($password, PASSWORD_DEFAULT);
        Database::update("users", ['password_hash'], [$password], ['id' => $id]);
        return true;
    }

    public static function createUser($username, $password, $email, $googleId = null, $googleEmail = null)
    {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $verified_code = bin2hex(random_bytes(16));
        Mailer::sendVerificationEmail($email, $username, $verified_code);
        return Database::insert("users", ['username', 'password_hash', 'email', 'verified', 'google_id', 'google_email'], [$username, $password, $email, $verified_code, $googleId, $googleEmail]);
    }

    public static function deleteUser($id)
    {
        GameSaves::transferSaveGames($id);
        GameSaves::deleteUserHasGameSavesByUser($id);
        return Database::delete("users", ['id' => $id]);
    }

    public static function verifyUser($code): bool
    {
        $user = Database::get("users", ['*'], [], ['verified' => $code]);
        if ($user) {
            Database::update("users", ['verified'], [1], ['id' => $user->id]);
            return true;
        }
        return false;
    }

    public static function resendVerificationEmail($username)
    {
        $user = Database::get("users", ['*'], [], ['username' => $username]);
        if ($user) {
            $verified_code = bin2hex(random_bytes(16));
            Database::update("users", ['verified'], [$verified_code], ['id' => $user->id]);
            Mailer::sendVerificationEmail($user->email, $user->username, $verified_code);
            return true;
        }
        return false;
    }

    public static function filterUsers($users, &$allowedUsers, &$requestUsers): array
    {
        // Filter out current session user from the lists
        $users = array_filter($users, function ($user) use ($allowedUsers, $requestUsers) {
            return !(
                in_array($user->id, array_column($allowedUsers, 'users_id')) ||
                $user->id == $_SESSION['userId'] ||
                in_array($user->id, array_column($requestUsers, 'users_id'))
            );
        });

        $requestUsers = array_filter($requestUsers, function ($user) {
            return $user->id != $_SESSION['userId'];
        });

        $allowedUsers = array_filter($allowedUsers, function ($user) {
            return $user->id != $_SESSION['userId'];
        });

        return [
            'users' => $users,
            'allowedUsers' => $allowedUsers,
            'requestUsers' => $requestUsers
        ];
    }
    public static function checkIfValidated($username) {
        $user = Database::get("users", ['*'], [], ['username' => $username]);
        if ($user) {
            if ($user->verified === '1') {
                return true;
            } else {
                return false;
            }
        } else {
            return ['error_code' => 1, 'error_message' => 'User does not exist'];
        }
    }

    public static function checkIfFirstProduction($user_id): bool {
        $user = Database::get("users", ['first_production'], [], ['id' => $user_id]);
        if ($user) {
            if ($user->first_production === 1) {
                Database::update("users", ['first_production'], [0], ['id' => $user_id]);
                return true;
            }
        }
        return false;
    }

    public static function checkIfFirstSaveGame($user_id): bool {
        $user = Database::get("users", ['first_save_game'], [], ['id' => $user_id]);
        if ($user) {
            if ($user->first_save_game === 1) {
                Database::update("users", ['first_save_game'], [0], ['id' => $user_id]);
                return true;
            }
        }
        return false;
    }

    public static function CheckVerificationStatus($userName, $email, $token) {
        if (self::getUserByUsername($userName) === false || self::getUserByEmail($email) === false) {
            return ['error_code' => 1, 'error_message' => 'User does not exist'];
        }

        $user = Database::get("users", ['*'], [], ['username' => $userName, 'email' => $email]);
        if ($user->verified === '1') {
            return ['error_code' => 2, 'error_message' => 'User is already verified'];
        }

        if ($user->verified !== $token) {
            return ['error_code' => 3, 'error_message' => 'Token is invalid please check your email for the correct link or resend the verification email by clicking the button below'];
        }

        return ['success' => 'User is ready to be verified'];
    }

    public static function unsubscribeUser($email) {
        $user = Database::get("users", ['*'], [], ['email' => $email]);
        if ($user) {
            Database::update("users", ['updates'], [0], ['id' => $user->id]);
            return true;
        }
        return false;
    }

    public static function getGoogleConnectedUser($googleId) {
        return Database::get("users", ['*'], [], ['google_id' => $googleId]);
    }

    public static function linkGoogleAccount($googleId, $googleEmail, $password, $userId = null) {
        $user = $userId ? self::getUserById($userId) : self::getUserByEmail($googleEmail);
        if ($user) {
            if (password_verify($password, $user->password_hash)) {
                Database::update("users", ['google_id', 'google_email'], [$googleId, $googleEmail], ['id' => $user->id]);

                return AuthControler::login($user->username, $password);
            }
        }
        return false;
    }

    public static function linkGoogleAccountBySession($googleId, $googleEmail) {
        $userId = $_SESSION['userId'];
        $user = self::getUserById($userId);

        Database::update("users", ['google_id', 'google_email'], [$googleId, $googleEmail], ['id' => $userId]);

    }

    public static function disconnectGoogleAccount(mixed $userId) {
        Database::update("users", ['google_id', 'google_email'], [null, null], ['id' => $userId]);
    }

    public static function handleProfileUpdate($user) {
        if (!isset($_POST['username']) || !isset($_POST['email'])) {
            return ['error' => 'Missing required fields'];
        }

        $username = $_POST['username'];
        $email = $_POST['email'];
        $updates = isset($_POST['updates']) ? 1 : 0;

        if (strlen($username) > 45) {
            return ['error' => 'Username exceeds the maximum allowed length. Please use up to 45 characters.'];
        } elseif (strlen($email) > 200) {
            return ['error' => 'Email is too lengthy. Please use an email under 200 characters.'];
        } elseif ($username !== strip_tags($username)) {
            return ['error' => 'Security Alert: Unauthorized characters detected in username.'];
        } elseif ($email !== strip_tags($email)) {
            return ['error' => 'Security Alert: The email contains restricted characters.'];
        } elseif (self::getUserByEmail($email) && $email !== $user->email) {
            return ['error' => 'Email already in use'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'Invalid email'];
        } elseif (self::getUserByUsername($username) && $username !== $user->username) {
            return ['error' => 'Username already in use'];
        }

        if (self::updateUser($user->id, $username, $email, $updates)) {
            return ['success' => true];
        } else {
            return ['error' => 'Error updating username'];
        }
    }

    public static function handlePasswordUpdate($user) {
        if (!isset($_POST['password']) || !isset($_POST['password2'])) {
            return ['error' => 'Missing password fields'];
        }

        $password = $_POST['password'];
        $password2 = $_POST['password2'];

        if ($password !== $password2) {
            return ['error' => 'Passwords do not match'];
        }

        if (self::updatePassword($user->id, $password)) {
            return ['success' => true];
        } else {
            return ['error' => 'Error updating password'];
        }
    }

    /**
     * @param int $userId
     *
     * @return bool
     */
    public static function deletePersonalData(int $userId): bool {
        $database = new NewDatabase();
        $database->beginTransaction();
        try {

            $database->delete(table: "login_attempts", where: ["users_id" => $userId]);
            $database->delete(table: "error_404_logs", where: ["users_id" => $userId]);
            $database->delete(table: "error_403_logs", where: ["users_id" => $userId]);

            $database->commit();
        } catch (Exception $e) {
            $database->rollBack();
            $_SESSION['error'] = "An error occurred while deleting your personal data. Please try again later.";
            return false;
        }

        $_SESSION['success'] = "Your personal data has been successfully deleted!";

        return true;
    }

    public static function getUpdateEmailUsers() {
        return Database::query("SELECT * FROM users WHERE updates = 1 AND verified = 1");
    }
}