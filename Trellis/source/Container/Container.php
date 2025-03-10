<?php

declare(strict_types=1);

namespace Cts\Trellis\Container;

use Psr\Container\ContainerInterface;
use Cts\Trellis\Exceptions\ContainerException;
use Cts\Trellis\Exceptions\ContainerServiceException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class Container implements ContainerInterface
{
    private array $services = [];
    private array $instances = [];
    private array $resolving = [];

    public function add(string $id, mixed $concrete = null): void
    {
        if ($this->has($id)) {
            throw new ContainerException("Service '$id' is already registered");
        }

        if ($concrete === null) {
            if (!class_exists($id)) {
                throw new ContainerException("Service '$id' could not be found.");
            }
            $concrete = $id;
        }

        if (interface_exists($id)) {
            if (!is_string($concrete) || !class_exists($concrete) || !in_array($id, class_implements($concrete))) {
                throw new ContainerException("Invalid implementation for interface '$id'");
            }
        }

        $this->services[$id] = is_callable($concrete) ? $concrete : fn() => $this->resolve($concrete);
    }

    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!$this->has($id)) {
            throw new ContainerServiceException("Service '$id' could not be resolved.");
        }

        $service = $this->services[$id];

        $object = is_callable($service) ? $service($this) : $this->resolve($service);

        $this->instances[$id] = $object;

        return $object;
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]) || class_exists($id);
    }

    private function resolve(string $class): object
    {
        if (isset($this->resolving[$class])) {
            throw new ContainerException("Circular dependency detected for '$class'");
        }

        $this->resolving[$class] = true;

        try {
            $object = $this->createInstance($class);
        } finally {
            unset($this->resolving[$class]);
        }

        return $object;
    }

    private function createInstance(string $class): object
    {
        try {
            $reflectionClass = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new ContainerException("Error resolving '$class': " . $e->getMessage());
        }

        $constructor = $reflectionClass->getConstructor();
        if ($constructor === null) {
            return $reflectionClass->newInstance();
        }

        $constructorParams = $constructor->getParameters();
        $classDependencies = $this->resolveClassDependencies($constructorParams);

        return $reflectionClass->newInstanceArgs($classDependencies);
    }

    private function resolveClassDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependencies[] = $this->resolveParameter($parameter);
        }

        return $dependencies;
    }

    private function resolveParameter(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type === null || $type->isBuiltin()) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            throw new ContainerException("Cannot resolve non-class parameter '{$parameter->getName()}'");
        }

        return $this->get($type->getName());
    }
}
