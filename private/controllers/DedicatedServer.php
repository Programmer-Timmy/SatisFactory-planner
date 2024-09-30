<?php

class DedicatedServer
{
    /**
     * @param int $saveGameId
     * @param string $ip
     * @param int $port
     * @param string $apiKey
     * @return void
     */
    public static function saveServer(int $saveGameId, string $ip, int $port, string $apiKey): void
    {
        $existingServer = self::getBySaveGameId($saveGameId);

        $serverTokenKey = getenv('SERVER_TOKEN_KEY');
        $ivLength = openssl_cipher_iv_length('aes-256-cbc'); // Get the IV length for AES-256-CBC
        $iv = openssl_random_pseudo_bytes($ivLength); // Generate a secure random IV

        // Encrypt the API key if it's not empty, and store the IV along with the encrypted data
        $encryptedApiKey = $apiKey !== '' ? openssl_encrypt($apiKey, 'aes-256-cbc', $serverTokenKey, 0, $iv) : '';
        $apiKeyWithIv = $encryptedApiKey !== '' ? base64_encode($iv . $encryptedApiKey) : '';

        if ($existingServer) {
            // Check for any changes and if the API key is not empty
            if ($existingServer->server_ip === $ip && $existingServer->server_port === $port && $existingServer->server_token === $apiKeyWithIv) {
                return; // No need to update if nothing has changed
            }

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


}