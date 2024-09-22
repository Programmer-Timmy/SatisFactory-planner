<?php

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