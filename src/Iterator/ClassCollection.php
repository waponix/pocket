<?php
namespace Waponix\Pocket\Iterator;

use Waponix\Pocket\Exception\ClassNotFoundException;

/**
 * Class ClassCollection
 * This iterator class will stack the dependencies first before the main class, to be later on read and loaded by the Pocket class
 * 
 */
class ClassCollection implements \Iterator
{
    private array $classes = [];
    private int $key = 0;

    public function __construct(string $class)
    {
        $this->collectClass($class);
    }

    private function collectClass(string $class): void
    {
        $parameters = [];

        if (!class_exists($class)) {
            throw new ClassNotFoundException('Class ' . $class . ' does not exist');
        }

        if (in_array($class, $this->classes)) {
            // do not insert again if already in the stack
            return;
        }

        if (!method_exists($class, '__construct')) {
            $this->classes[] = $class;
            return;
        }

        $reflectionMethod = new \ReflectionMethod($class, '__construct');
        $parameters = $reflectionMethod->getParameters();

        foreach ($parameters as $parameter) {
            if (!$parameter instanceof \ReflectionParameter) continue;

            if (!$parameter->getType()->isBuiltin()) {
                $this->collectClass($parameter->getType());
            }
        }

        // push the class is at the end of this function so that the very end dependency will be first in the stack
        $this->classes[] = $class;
    }

    public function current(): mixed
    {
        return $this->classes[$this->key];
    }

    public function rewind(): void
    {
        $this->key = 0;
    }

    public function next(): void
    {
        $this->key += 1;
    }

    public function valid(): bool
    {
        return isset($this->classes[$this->key]);
    }

    public function key(): int
    {
        return $this->key;
    }
}