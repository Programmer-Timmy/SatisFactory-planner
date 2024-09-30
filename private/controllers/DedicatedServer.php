<?php

class DedicatedServer
{
    /**
     * @param int $saveGameId
     * @param string $ip
     * @param int $port
     * @param string $apiKey
     * @return string[]
     */
    public static function saveServer(int $saveGameId, string $ip, int $port, string|null $password): array|null
    {
        $existingServer = self::getBySaveGameId($saveGameId);
        if ($existingServer) {
            if ($existingServer->server_ip === $ip && $existingServer->server_port === $port) {
                return null; // No changes, return early
            }
        }

        $serverTokenKey = getenv('SERVER_TOKEN_KEY');
        $ivLength = openssl_cipher_iv_length('aes-256-cbc'); // Get the IV length for AES-256-CBC
        $iv = openssl_random_pseudo_bytes($ivLength); // Generate a secure random IV

        if ($password) {
            $apiKey = self::PasswordLogin($ip, $port, $password);
        } else {
            $apiKey = self::PasswordlessLogin($ip, $port);
        }

        if (!$apiKey) {
            return ['status' => 'error', 'message' => 'Failed to login to the server. Please check the IP, port, and password'];
        }

        // Encrypt the API key if it's not empty, and store the IV along with the encrypted data
        $encryptedApiKey = $apiKey !== '' ? openssl_encrypt($apiKey, 'aes-256-cbc', $serverTokenKey, 0, $iv) : '';
        $apiKeyWithIv = $encryptedApiKey !== '' ? base64_encode($iv . $encryptedApiKey) : '';

        try {
            if ($existingServer) {
                // Update the server info in the database
                Database::update(
                    table: 'dedicated_server',
                    columns: ['server_ip', 'server_port', 'server_token'],
                    values: [$ip, $port, $apiKeyWithIv],
                    where: ['game_saves_id' => $saveGameId]
                );
            } else {
                // Insert a new server record if it doesn't exist
                Database::insert(
                    table: 'dedicated_server',
                    columns: ['game_saves_id', 'server_ip', 'server_port', 'server_token'],
                    values: [$saveGameId, $ip, $port, $apiKeyWithIv]
                );
            }
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Failed to save server. Please try again later'];
        }

        return ['status' => 'success', 'message' => 'Server saved successfully'];
    }

    /**
     * @param int $saveGameId
     * @return mixed
     */
    public static function getBySaveGameId(int $saveGameId)
    {
        $server = Database::get(table: 'dedicated_server', where: ['game_saves_id' => $saveGameId]);
        if ($server) {
            if ($server->server_token !== '') {
                // Decode the base64 encoded string to get the IV and encrypted data
                $encodedData = base64_decode($server->server_token);
                $ivLength = openssl_cipher_iv_length('aes-256-cbc'); // Get the IV length for AES-256-CBC
                $iv = substr($encodedData, 0, $ivLength); // Extract the IV
                $encryptedApiKey = substr($encodedData, $ivLength); // Extract the encrypted API key

                // Decrypt the API key using the IV and server token key
                $server->server_token = openssl_decrypt($encryptedApiKey, 'aes-256-cbc', getenv('SERVER_TOKEN_KEY'), 0, $iv);
            } else {
                $server->server_token = ''; // Handle case where there is no API key
            }
        }
        return $server;
    }

    /**
     * @param int $saveGameId
     * @return void
     */
    public static function deleteServer(int $saveGameId): void
    {
        Database::delete(table: 'dedicated_server', where: ['game_saves_id' => $saveGameId]);
    }

    /**
     * @param string $ip
     * @param int $port
     * @return string|bool Returns the authentication token or false if the request fails
     */
    public static function PasswordlessLogin(string $ip, int $port): string|bool
    {
        $api = new APIClient($ip, $port);

        $response = $api->post('PasswordlessLogin', ["MinimumPrivilegeLevel" => 'client']);

        if ($response['response_code'] !== 200) {
            return false;
        }

        return $response['data']['authenticationToken'] ?? false;
    }

    /**
     * @param string $ip
     * @param int $port
     * @param string $password
     * @return string|bool Returns the authentication token or false if the request fails
     */
    public static function PasswordLogin(string $ip, int $port, string $password): string|bool
    {
        $api = new APIClient($ip, $port);

        $response = $api->post('PasswordLogin', ['MinimumPrivilegeLevel' => 'client', 'Password' => $password]);

        if ($response['response_code'] !== 200) {
            return false;
        }

        return $response['data']['authenticationToken'] ?? false;
    }

}