<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use Cts\Trellis\Core\Response;

class UserController
{
    private array $users = [
        1 => ['id' => 1, 'name' => 'John Doe'],
        2 => ['id' => 2, 'name' => 'Jane Smith'],
    ];

    public function index(): Response
    {
        return new Response(json_encode($this->users));
    }

    public function show($id): Response
    {
        if (!isset($this->users[$id])) {
            return new Response(json_encode(['error' => 'User not found']), 404);
        }
        return new Response(json_encode($this->users[$id]), 200);
    }


    public function store(): Response
    {
        return new Response(json_encode(['message' => 'User created successfully']), 201);
    }
}
