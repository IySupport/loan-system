<?php

class Router
{
    private array $routes = [];

    public function add(string $method, string $pattern, array $handler): void
    {
        $this->routes[] = ['method' => strtoupper($method), 'pattern' => $pattern, 'handler' => $handler];
    }

    public function get(string $pattern, array $handler): void  { $this->add('GET', $pattern, $handler); }
    public function post(string $pattern, array $handler): void { $this->add('POST', $pattern, $handler); }

    public function dispatch(string $uri, string $method): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        if ($base !== '/' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        $uri = '/' . trim($uri, '/');
        if ($uri === '/') { $uri = '/dashboard'; }

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }
            $pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $route['pattern']);
            if (preg_match('#^' . $pattern . '$#', $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                [$class, $action] = $route['handler'];
                $controller = new $class();
                call_user_func_array([$controller, $action], $params);
                return;
            }
        }

        http_response_code(404);
        echo '404 - Page not found';
    }
}
