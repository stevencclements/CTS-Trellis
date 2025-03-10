<?php

declare(strict_types=1);

namespace App\Controllers;

use Cts\Trellis\Core\Response;

class BaseController
{
    public function index(): Response
    {
        $content = '<h1>Hello world!</h1>';

        $response = new Response($content);

        return $response;
    }
}
