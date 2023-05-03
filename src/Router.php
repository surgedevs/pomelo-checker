<?php

class Router
{
    private $routes = [];

    public function addRoute(string $path, string | callable $handler, array | string $methods = [])
    {
        if (!is_array($methods)) {
            $methods = [$methods];
        }

        if (empty($methods)) {
            $methods = ['POST', 'GET', 'PUT', 'DELETE', 'HEAD', 'CONNECT', 'OPTIONS', 'TRACE', 'PATCH'];
        }

        $path = str_replace('/', '\/', $path);
        $path = preg_replace('/\{([^\}]+)\}/', '(?<$1>[^\/]+)?', $path);
        $path = preg_replace('/\{([^\}]+)\?\}/', '(?<$1>[^\/]+)?', $path);

        foreach ($methods as $method) {
            $this->routes[$method][$path] = $handler;
        }
    }


    public function route(string $method, string $uri): void
    {
        if (!isset($this->routes)) {
            $this->notFound();

            return;
        }

        $pos = strpos($uri, '?');
        if ($pos !== false) {
            $uri = substr($uri, 0, $pos);
        }

        foreach ($this->routes[$method] as $route => $handler) {
            // $route = str_replace('/', '\/', $route);
            $route = preg_replace('/\{([^\}]+)\}/', '(?<$1>[^\/]+)?', $route);
            $route = preg_replace('/\{([^\}]+)\?\}/', '(?<$1>[^\/]+)?', $route);

            if (preg_match('/^' . $route . '$/', $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                if (is_callable($handler)) {
                    call_user_func_array($handler, $params);
                } else {
                    $parts = explode('@', $handler);
                    $controller = $parts[0];
                    $method = $parts[1];
                    $controllerInstance = new $controller();

                    call_user_func_array([$controllerInstance, $method], $params);
                }

                return;
            }
        }

        $this->notFound();
    }


    public function notFound(): void
    {
        echo '404 Not found';
        header('Status: 404');
    }
}