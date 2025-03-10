<?php

declare(strict_types=1);

namespace Cts\Trellis\Exceptions;

use Psr\Container\ContainerExceptionInterface;

class ContainerException extends TrellisException implements ContainerExceptionInterface
{
    public int $statusCode;

    public function __construct(
        string $message = '',
        int $statusCode = 500,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
    }
}
