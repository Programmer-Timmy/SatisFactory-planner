<?php


// Define the API functions array
$apiFunctions = [
    'HealthCheck' => [
        'requires_auth' => false,
        'parameters' => [
            'ClientCustomData' => [
                'type' => 'string',
                'required' => false,
                'default' => ''
            ]
        ],
        'multipart' => false
    ],
    'VerifyAuthenticationToken' => [
        'requires_auth' => false,
        'parameters' => [],
        'multipart' => false
    ],
    'PasswordlessLogin' => [
        'requires_auth' => false,
        'parameters' => [
            'MinimumPrivilegeLevel' => [
                'type' => 'enum',
                'options' => ['NotAuthenticated', 'Client', 'Administrator', 'InitialAdmin', 'APIToken'],
                'required' => true
            ]
        ],
        'multipart' => false
    ],
    'PasswordLogin' => [
        'requires_auth' => false,
        'parameters' => [
            'MinimumPrivilegeLevel' => [
                'type' => 'enum',
                'options' => ['NotAuthenticated', 'Client', 'Administrator', 'InitialAdmin', 'APIToken'],
                'required' => true
            ],
            'Password' => [
                'type' => 'password',
                'required' => true
            ]
        ],
        'multipart' => false
    ],
    'QueryServerState' => [
        'requires_auth' => true,
        'parameters' => [],
        'multipart' => false
    ],
    'GetServerOptions' => [
        'requires_auth' => true,
        'parameters' => [],
        'multipart' => false
    ],
    'GetAdvancedGameSettings' => [
        'requires_auth' => true,
        'parameters' => [],
        'multipart' => false
    ],
    'ApplyAdvancedGameSettings' => [
        'requires_auth' => true,
        'parameters' => [
            'AppliedAdvancedGameSettings' => [
                'type' => 'dict',
                'required' => true
            ]
        ],
        'multipart' => false
    ],
    'ClaimServer' => [
        'requires_auth' => false,
        'parameters' => [
            'ServerName' => [
                'type' => 'string',
                'required' => true
            ],
            'AdminPassword' => [
                'type' => 'password',
                'required' => true
            ]
        ],
        'multipart' => false
    ],
    'RenameServer' => [
        'requires_auth' => true,
        'parameters' => [
            'ServerName' => [
                'type' => 'string',
                'required' => true
            ]
        ],
        'multipart' => false
    ],
    'SetClientPassword' => [
        'requires_auth' => true,
        'parameters' => [
            'Password' => [
                'type' => 'password',
                'required' => true
            ]
        ],
        'multipart' => false
    ],
    'SetAdminPassword' => [
        'requires_auth' => true,
        'parameters' => [
            'Password' => [
                'type' => 'password',
                'required' => true
            ],
            'AuthenticationToken' => [
                'type' => 'string',
                'required' => true
            ]
        ],
        'multipart' => false
    ],
    'SetAutoLoadSessionName' => [
        'requires_auth' => true,
        'parameters' => [
            'SessionName' => [
                'type' => 'string',
                'required' => true
            ]
        ],
        'multipart' => false
    ],
    'RunCommand' => [
        'requires_auth' => true,
        'parameters' => [
            'Command' => [
                'type' => 'string',
                'required' => true
            ]
        ],
        'multipart' => false
    ],
    'Shutdown' => [
        'requires_auth' => true,
        'parameters' => [],
        'multipart' => false
    ],
    'ApplyServerOptions' => [
        'requires_auth' => true,
        'parameters' => [
            'UpdatedServerOptions' => [
                'type' => 'dict',
                'required' => true
            ]
        ],
        'multipart' => false
    ],
    'CreateNewGame' => [
        'requires_auth' => true,
        'parameters' => [
            'NewGameData' => [
                'type' => 'dict',
                'required' => true,
                'schema' => [
                    'SessionName' => [
                        'type' => 'string',
                        'required' => true
                    ],
                    'MapName' => [
                        'type' => 'string',
                        'required' => false
                    ],
                    'StartingLocation' => [
                        'type' => 'string',
                        'required' => false
                    ],
                    'SkipOnboarding' => [
                        'type' => 'boolean',
                        'required' => false
                    ],
                    'AdvancedGameSettings' => [
                        'type' => 'dict',
                        'required' => false
                    ],
                    'CustomOptionsOnlyForModding' => [
                        'type' => 'dict',
                        'required' => false
                    ]
                ]
            ]
        ],
        'multipart' => false
    ],
    'SaveGame' => [
        'requires_auth' => true,
        'parameters' => [
            'SaveName' => [
                'type' => 'string',
                'required' => true
            ]
        ],
        'multipart' => false
    ],
    'DeleteSaveFile' => [
        'requires_auth' => true,
        'parameters' => [
            'SaveName' => [
                'type' => 'string',
                'required' => true
            ]
        ],
        'multipart' => false
    ],
    'DeleteSaveSession' => [
        'requires_auth' => true,
        'parameters' => [
            'SessionName' => [
                'type' => 'string',
                'required' => true
            ]
        ],
        'multipart' => false
    ],
    'EnumerateSessions' => [
        'requires_auth' => true,
        'parameters' => [],
        'multipart' => false
    ],
    'LoadGame' => [
        'requires_auth' => true,
        'parameters' => [
            'SaveName' => [
                'type' => 'string',
                'required' => true
            ],
            'EnableAdvancedGameSettings' => [
                'type' => 'boolean',
                'required' => false
            ]
        ],
        'multipart' => false
    ],
    'UploadSaveGame' => [
        'requires_auth' => true,
        'parameters' => [
            'SaveName' => [
                'type' => 'string',
                'required' => true
            ],
            'LoadSaveGame' => [
                'type' => 'boolean',
                'required' => false
            ],
            'EnableAdvancedGameSettings' => [
                'type' => 'boolean',
                'required' => false
            ]
        ],
        'multipart' => true
    ],
    'DownloadSaveGame' => [
        'requires_auth' => true,
        'parameters' => [
            'SaveName' => [
                'type' => 'string',
                'required' => true
            ]
        ],
        'multipart' => false
    ]
];

class APIClient
{
    private $host;
    private $port;
    private $authToken;

    public function __construct($host, $port = 7777, $authToken = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->authToken = $authToken;
    }

    public function post($function, $data = [], $files = [])
    {
        // Define API functions and required parameters
        global $apiFunctions;
        if (!isset($apiFunctions[$function])) {
            throw new Exception("Unknown API function: $function");
        }

        $apiFunction = $apiFunctions[$function];

        // Validate parameters
        $this->validateParameters($apiFunction, $data);

        $url = "https://{$this->host}:{$this->port}/api/v1";

        $ch = curl_init($url);

        $headers = [
            'Content-Type: application/json'
        ];

        if ($this->authToken && $apiFunction['requires_auth']) {
            $headers[] = 'Authorization: Bearer ' . $this->authToken;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Ignore SSL certificate validation
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if ($apiFunction['multipart']) {
            $postFields = $this->prepareFiles($data, $files);
        } else {
            $payload = json_encode([
                'function' => $function,
                'data' => $data
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        curl_setopt($ch, CURLOPT_POST, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL Error: $error");
        }

        curl_close($ch);

        return $this->handleResponse($response);
    }

    private function validateParameters($apiFunction, $data)
    {
        foreach ($apiFunction['parameters'] as $param => $info) {
            if ($info['required'] && !array_key_exists($param, $data)) {
                throw new Exception("Missing required parameter: $param");
            }
            // Additional type checking can be done here
        }
    }

    private function prepareFiles($data, $files)
    {
        $postFields = ['data' => json_encode($data)];
        foreach ($files as $key => $file) {
            $postFields[$key] = new CURLFile($file['tmp_name'], $file['type'], $file['name']);
        }
        return $postFields;
    }

    private function handleResponse($response)
    {
        $decoded = json_decode($response, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        } else {
            return [
                'raw_response' => $response
            ];
        }
    }
}


function secondsToHMS($seconds)
{
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;

    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}

$totalGameDuration = 72453;
$formattedDuration = secondsToHMS($totalGameDuration);

echo $formattedDuration; // Output: 20:07:33


// Usage
try {
    $client = new APIClient('localhost', 7777, 'your_auth_token');
    $response = $client->post('QueryServerState');
    foreach ($response['data']['serverGameState'] as $key => $value) {
        // bit of bootstrap styling
        if ($key === 'totalGameDuration') {
            $value = secondsToHMS($value);
        }
        echo "<div class='row'><div class='col-6'>$key</div><div class='col-6'>$value</div></div>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
