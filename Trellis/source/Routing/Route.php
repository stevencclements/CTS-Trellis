<?php

declare(strict_types=1);

namespace Cts\Trellis\Routing;

class Route
{
    public function __construct(
        public readonly string $path,
        public readonly string $method,
        public readonly mixed $handler,
        public readonly bool $isApi = false
    ) {}
}
