<?php

declare(strict_types=1);

namespace App\Controllers;

use Cts\Trellis\Core\Response;

class PostController
{
    public function show(string $id): Response
    {
        $content = "<h1>Post: {$id}</h1>";

        $response = new Response($content);

        return $response;
    }
}
