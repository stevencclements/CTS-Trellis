<?php

declare(strict_types=1);

namespace App\Routes;

use App\Controllers\Api\UserController;
use Cts\Trellis\Routing\Route;

return [
    new Route('/cts-trellis/api/users', 'GET', [UserController::class, 'index'], true),
    new Route('/cts-trellis/api/users/{id}', 'GET', [UserController::class, 'show'], true),
    new Route('/cts-trellis/api/users', 'POST', [UserController::class, 'store'], true),
];
