<?php
namespace src\Pocket\ClassIterator;

/**
 * Class ClassIterator
 * @package src\Pocket\ClassIterator
 * This iterator class will stack the dependencies first before the main class, to be later on read and loaded by the Pocket class
 */
class ClassIterator implements \Iterator
{
    private $classes = [];
    private $key = 0;

    public function __construct(string $class)
    {
        $this->collectClass($class);
    }

    private function collectClass(string $class)
    {
        $parameters = [];

        if (method_exists($class, '__construct')) {
            $reflectionMethod = new \ReflectionMethod($class, '__construct');
            $parameters = $reflectionMethod->getParameters();
        }

        foreach ($parameters as $parameter) {
            if (!$parameter instanceof \ReflectionParameter) continue;

            if (!$parameter->getType()->isBuiltin()) {
                $this->collectClass($parameter->getType());
            }
        }

        // the push call is at the end of this function so that the very end dependency will be first in the stack
        $this->classes[] = $class;
    }

    public function current()
    {
        return $this->classes[$this->key];
    }

    public function rewind()
    {
        $this->key = 0;
        return $this;
    }

    public function next()
    {
        $this->key += 1;
    }

    public function valid()
    {
        return isset($this->classes[$this->key]);
    }

    public function key()
    {
        return $this->key;
    }
}