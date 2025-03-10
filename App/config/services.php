<?php

use Cts\Trellis\Container\Container;
use Cts\Trellis\Core\Request;
use Cts\Trellis\Core\Response;
use Cts\Trellis\Core\ServerRequest;
use Cts\Trellis\Core\Stream;
use Cts\Trellis\Core\UploadedFile;
use Cts\Trellis\Core\Uri;
use Cts\Trellis\Routing\Kernel;
use Cts\Trellis\Routing\Router;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

$container = new Container();

$container->add(RequestInterface::class, Request::class);
$container->add(ServerRequestInterface::class, ServerRequest::class);
$container->add(ResponseInterface::class, Response::class);
$container->add(StreamInterface::class, Stream::class);
$container->add(UriInterface::class, Uri::class);
$container->add(UploadedFileInterface::class, UploadedFile::class);

$container->add('Kernel', Kernel::class);
$container->add('Router', Router::class);

dd($container);

return $container;
