<?php
class Router {
    private static array $routes = [];

    public static function get(string $pattern, callable $callback) {
        self::$routes['GET'][] = ['pattern' => $pattern, 'callback' => $callback];
    }

    public static function post(string $pattern, callable $callback) {
        self::$routes['POST'][] = ['pattern' => $pattern, 'callback' => $callback];
    }

    public static function dispatch(): bool {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

        foreach (self::$routes[$method] ?? [] as $route) {
            $pattern = preg_replace('#\{[a-zA-Z_]+\}#', '([a-zA-Z0-9_-]+)', $route['pattern']);
            $pattern = "#^$pattern$#";

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                call_user_func_array($route['callback'], $matches);
                return true;
            }
        }

        http_response_code(404);
        return false;
    }
}
