<?php

declare(strict_types=1);

namespace Cts\Trellis\Routing;

use Cts\Trellis\Core\Request;
use Cts\Trellis\Core\Response;
use Cts\Trellis\Routing\Route;
use Cts\Trellis\Routing\Router;

class Kernel
{
    private Router $router;

    public function __construct()
    {
        $this->router = new Router();
        $this->loadRoutes();
    }

    private function loadRoutes(): void
    {
        $webRoutes = require BASE_PATH . '/App/Routes/web.php';
        $apiRoutes = require BASE_PATH . '/App/Routes/api.php';

        $routes = array_merge(is_array($webRoutes) ? $webRoutes : [], is_array($apiRoutes) ? $apiRoutes : []);

        foreach ($routes as $route) {
            if ($route instanceof Route) {
                $this->router->register($route);
            }
        }
    }


    public function dispatch(Request $request): Response
    {
        return $this->router->route($request);
    }
}
