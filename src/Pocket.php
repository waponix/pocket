<?php
namespace Pocket;

use Pocket\Exception\ClassNotFoundException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class Pocket
{
    private array $classPouch = [];
    private readonly Pouch $pouch;

    public function __construct()
    {
        $this->pouch = new Pouch('./src/pocketcache');
    }
    
    public function &get(string $class): ?object
    {
        return $this->loadObject($class);
    }

    private function &loadObject(string $class)
    {
        // try loading the object from the cache
        $object = &$this->pouch->get($class);

        if (is_object($object) && get_class($object) === $class) {
            return $object;
        }

        $classCollector = new ClassCollector($class);

        foreach ($classCollector as $class) {
           $reflectionClass = new ReflectionClass($class);
           $parameters = $this->collectParameters($reflectionClass);

           $object = match (true) {
                is_array($parameters) => $reflectionClass->newInstanceArgs($parameters),
                $parameters === null => $reflectionClass->newInstance()
           };

           $this->pouch->add($object);
        }

        return $this->pouch->get($class);
    }

    private function collectParameters(ReflectionClass $reflectionClass): ?array
    {
        try {
            $parameters = $reflectionClass->getMethod('__construct')->getParameters();
            $parameterRealValues = [];
            foreach ($parameters as $parameter) {
                if (!$parameter instanceof ReflectionParameter) continue;

                // TODO: for builtin parameters (e.g. string, integer) should be able to get value from a configuration

                // try to load class
                if (!$parameter->getType()->isBuiltin()) {
                    $parameterRealValues[$parameter->getPosition()] = $this->loadObject((string) $parameter->getType());
                }
            }
        } catch (ReflectionException $exception) {
            $parameterRealValues = null;
        }
        try {
            $parameters = $reflectionClass->getMethod('__construct')->getParameters();
            $parameterRealValues = [];
            foreach ($parameters as $parameter) {
                if (!$parameter instanceof ReflectionParameter) continue;

                // TODO: for builtin parameters (e.g. string, integer) should be able to get value from a configuration

                // try to load class
                if (!$parameter->getType()->isBuiltin()) {
                    $parameterRealValues[$parameter->getPosition()] = $this->loadObject((string) $parameter->getType());
                }
            }
        } catch (ReflectionException $exception) {
            $parameterRealValues = null;
        }

        return $parameterRealValues;
        return $parameterRealValues;
    }
}