<?php
class Response {
    public static function success(array $data = [], int $count = null): void {
        $count = $count ?? count($data);
        echo json_encode([
                             'success' => true,
                             'length' => $count,
                             'data' => $data
                         ]);
        exit();
    }

    public static function error(string $message, int $code = 400): void {
        http_response_code($code);
        echo json_encode([
                             'success' => false,
                             'error' => $message
                         ]);
        exit();
    }
}
