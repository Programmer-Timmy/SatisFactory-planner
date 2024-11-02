<?php

class Users
{
    public static function getAllUsers()
    {
        return Database::getAll("users");
    }

    public static function getAllValidatedUsers()
    {
        return Database::getAll("users", ['*'], [], ['verified' => 1]);
    }

    public static function searchUsers($search)
    {
        return Database::query("SELECT * FROM users WHERE username LIKE ? and verified = 1", ["%$search%"]);
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

    public static function createUser($username, $password, $email)
    {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $verified_code = bin2hex(random_bytes(16));
        Mailer::sendVerificationEmail($email, $username, $verified_code);
        return Database::insert("users", ['username', 'password_hash', 'email', 'verified'], [$username, $password, $email, $verified_code]);
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
            return $user->users_id != $_SESSION['userId'];
        });

        $allowedUsers = array_filter($allowedUsers, function ($user) {
            return $user->users_id != $_SESSION['userId'];
        });

        return [
            'users' => $users,
            'allowedUsers' => $allowedUsers,
            'requestUsers' => $requestUsers
        ];
    }

    public static function generateUserListHTML($users, $game_id, $class, $buttonText, $buttonId)
    {

        $html = '';
        foreach ($users as $user) {
            $userId = $user->id ?? $user->users_id;
            $html .= '<div class="card mb-2 p-2">
        <div class="card-body d-flex justify-content-between align-items-center p-0">
            <h6 class="mb-1">' . htmlspecialchars($user->username) . '</h6>
            <button type="button" class="btn ' . $class . '"
                    user-id="' . htmlspecialchars($userId) . '"
                    game-id="' . htmlspecialchars($game_id) . '">' . $buttonText . '</button>
        </div>
    </div>';
        }
        return $html;
    }

    public static function checkIfValidated($username) {
        $user = Database::get("users", ['*'], [], ['username' => $username]);
        if ($user) {
            if ($user->verified === '0') {
                return true;
            }
        }
        return false;
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


}