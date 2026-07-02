<?php

namespace App\Core;

class Router {
    private $routes = [];

    public function get($uri, $callback) {
        $this->addRoute('GET', $uri, $callback);
    }

    public function post($uri, $callback) {
        $this->addRoute('POST', $uri, $callback);
    }

    private function addRoute($method, $uri, $callback) {
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'callback' => $callback
        ];
    }

    public function resolve() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        
        // Remove query string and the directory where public/index.php lives.
        $path = parse_url($uri, PHP_URL_PATH);
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $scriptDir = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');

        if ($scriptDir !== '' && strpos($path, $scriptDir) === 0) {
            $path = substr($path, strlen($scriptDir));
        } else {
            $fallbackBase = '/NeivActiva';
            if (strpos($path, $fallbackBase) === 0) {
                $path = substr($path, strlen($fallbackBase));
            }
        }

        if ($path === '' || $path === false) {
            $path = '/';
        }

        foreach ($this->routes as $route) {
            // Convert route to regex for variables if needed. Here we keep it simple or use exact match.
            // A simple param matcher:
            $routePattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $route['uri']);
            $routePattern = "#^" . $routePattern . "$#";

            if ($route['method'] === $method && preg_match($routePattern, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                if (is_callable($route['callback'])) {
                    return call_user_func_array($route['callback'], array_values($params));
                }

                if (is_array($route['callback'])) {
                    $controller = new $route['callback'][0]();
                    $action = $route['callback'][1];
                    return call_user_func_array([$controller, $action], array_values($params));
                }
            }
        }

        // 404
        http_response_code(404);
        echo View::render('errors/404');
    }
}
