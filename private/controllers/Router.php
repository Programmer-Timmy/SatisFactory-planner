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
        if ($uri === '') {
            $uri = 'home';
        }

        foreach (self::$routes[$method] ?? [] as $route) {
            $pattern = self::createPattern($route['pattern']);

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                call_user_func_array($route['callback'], $matches);
                return true;
            }
        }

        // Fallback to page-based system
        self::handlePageRequest($uri);
        return true;
    }

    public static function isRoute(string $uri, bool $ignoreMethod = false): bool {
        if ($ignoreMethod) {
            foreach (self::$routes as $methodRoutes) {
                foreach ($methodRoutes as $route) {
                    $pattern = self::createPattern($route['pattern']);
                    if (preg_match($pattern, $uri)) {
                        return true;
                    }
                }
            }
            return false;
        }

        $method = $_SERVER['REQUEST_METHOD'];

        foreach (self::$routes[$method] ?? [] as $route) {
            $pattern = self::createPattern($route['pattern']);
            if (preg_match($pattern, $uri)) {
                return true;
            }
        }

        return false;
    }

    private static function createPattern(string $pattern): string {
        return "#^" . preg_replace('#\{[a-zA-Z_]+\}#', '([a-zA-Z0-9_-]+)', $pattern) . "$#";
    }

    private static function handlePageRequest(string $uri): void {
        $requestedPage = '/' . $uri;

        if (is_dir(__DIR__ . "/../views/pages$requestedPage")) {
            $requestedPage .= '/index';
        }

        $pageTemplate = __DIR__ . "/../views/pages$requestedPage.php";

        if (file_exists($pageTemplate)) {
            include $pageTemplate;
        } else {
            self::handle404($requestedPage);
        }

        self::includeFooter();
        self::handlePopup();
    }

    private static function handle404(string $requestedPage): void {
        ErrorHandeler::blockIPForRapid404Errors($_SERVER['REMOTE_ADDR']);
        ErrorHandeler::add404Log(
            $requestedPage,
            $_SERVER['HTTP_REFERER'] ?? null,
            $_SESSION['userId'] ?? null
        );

        http_response_code(404);
        include __DIR__ . '/../views/errors/404.php';
    }

    private static function includeFooter(): void {
        include __DIR__ . '/../views/templates/footer.php';
    }

    private static function handlePopup(): void {
        global $site;

        if (!empty($site['showPopup']) && !isset($_SESSION['popupShown'])) {
            include __DIR__ . '/../views/Popups/popup.php';
            $_SESSION['popupShown'] = true;
        }
    }

}
