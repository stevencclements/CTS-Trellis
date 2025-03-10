<?php

declare(strict_types=1);

namespace App\Routes;

use App\Controllers\BaseController;
use App\Controllers\PostController;
use Cts\Trellis\Core\Response;
use Cts\Trellis\Routing\Route;

return [
    new Route('/cts-trellis/', 'GET', [BaseController::class, 'index']),
    new Route('/cts-trellis/posts/{id}', 'GET', [PostController::class, 'show']),
    new Route('/cts-trellis/hello/{name}', 'GET', function($name) {
        return new Response("<h1>Hello {$name}!</h1>");
    }),
];
