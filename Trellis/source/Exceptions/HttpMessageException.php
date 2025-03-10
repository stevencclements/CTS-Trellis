<?php

declare(strict_types=1);

namespace Cts\Trellis\Exceptions;

class HttpMessageException extends TrellisException
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
