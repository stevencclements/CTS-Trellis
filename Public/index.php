<?php

declare(strict_types=1);

use Psr\Http\Message\RequestInterface;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/App/config/services.php';

$request = $container->get(RequestInterface::class);
$kernel = $container->get('Kernel');

$response = $kernel->dispatch($request);

$response->render();
