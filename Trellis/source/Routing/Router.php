<?php

declare(strict_types=1);

namespace Cts\Trellis\Routing;

use Cts\Trellis\Core\Request;
use Cts\Trellis\Core\Response;
use Cts\Trellis\Routing\Route;

class Router
{
    private array $routes = [];

    public function register(Route $route): void
    {
        $this->routes[] = $route;
    }

    public function route(Request $request): Response
    {
        $requestUri = $request->getRequestTarget();
        $requestMethod = $request->getMethod();

        foreach ($this->routes as $route) {
            $params = [];
            if ($this->match($route->path, $requestUri, $params) && $route->method === $requestMethod) {
                return $this->executeHandler($route, $params);
            }
        }

        return new Response(json_encode(['error' => 'Not Found']), 404);
    }

    private function match(string $routePath, string $requestUri, ?array &$params = []): bool
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $requestUri, $matches)) {
            foreach ($matches as $key => $value) {
                if (!is_int($key)) {
                    $params[$key] = $value;
                }
            }
            return true;
        }

        return false;
    }

    private function executeHandler(Route $route, ?array $params = []): Response
    {
        $handler = $route->handler;

        if (is_callable($handler)) {
            $response = $handler(...$params);
        } elseif (is_array($handler)) {
            [$controller, $method] = $handler;
            $controllerInstance = new $controller();
            $response = $controllerInstance->$method(...$params);
        } else {
            return new Response('Internal Server Error', 500);
        }

        if (!$response instanceof Response) {
            $response = $route->isApi ? new Response(json_encode($response), 200) : new Response((string) $response, 200);
        }

        return $response;
    }

}
