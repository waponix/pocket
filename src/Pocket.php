<?php
namespace Waponix\Pocket;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Waponix\Pocket\Attribute\Service;
use Waponix\Pocket\Exception\ClassException;
use Waponix\Pocket\Exception\ClassNotFoundException;
use Waponix\Pocket\Exception\ParameterNotFoundException;
use Waponix\Pocket\Iterator\ClassCollector;

class Pocket
{
    private readonly Pouch $pouch;
    private array $parameters = [];

    public function __construct(array $parameters = [])
    {
        $this->pouch = new Pouch('./src/pocketcache');
        $this->parameters = $parameters;
    }
    
    public function &get(string $class): ?object
    {
        return $this->loadObject($class);
    }

    public function invoke(string $class, string $method): mixed
    {
        if (!class_exists($class)) {
            throw new ClassNotFoundException($class . ' was not found');
        }

        if (!method_exists($class, $method)) {
            throw new ClassException('The class ' . $class . ' does not have or has no public access to call method ' . $method . '()');
        }

        $reflectionMethod = new ReflectionMethod($class, $method);
        
        if (!$reflectionMethod->isPublic()) {
            throw new ClassException('The class ' . $class . ' has no public access to call method ' . $method . '()');
        }

        $args = $this->collectParameters($reflectionMethod);
        $object = $this->get($class);

        return match (true) {
            is_array($args) => $reflectionMethod->invokeArgs($object, $args),
            $args === null => $reflectionMethod->invoke($object)
        };
    }

    public function setParameters(array $parameters): Pocket
    {
        $this->parameters = $parameters;
        return $this;
    }

    private function &loadObject(string $class): ?object
    {
        // try loading the object from the cache
        $object = &$this->pouch->get($class);

        if (is_object($object) && get_class($object) === $class) {
            return $object;
        }

        $classCollector = new ClassCollector($class);

        foreach ($classCollector as $class) {
           $reflectionClass = new ReflectionClass($class);
           $parameters = null;

           if (method_exists($class, '__construct')) {
                $reflectionMethod = $reflectionClass->getMethod('__construct');

                if (!$reflectionMethod->isPublic()) {
                    throw new ClassException('The method __construct of class ' . $class . ' is not accessible or not defined as public');
                }

                $metaArgs = $this->getMetaArgs($reflectionClass); // get the meta args from the class level
                $parameters = $this->collectParameters($reflectionMethod, $metaArgs);
           }

           $object = match (true) {
                is_array($parameters) => $reflectionClass->newInstanceArgs($parameters),
                $parameters === null => $reflectionClass->newInstance()
           };

           $this->pouch->add($object);
        }

        return $this->pouch->get($class);
    }

    private function collectParameters(ReflectionMethod $reflectionMethod, array $metaArgs = []): ?array
    {
        $parameters = $reflectionMethod->getParameters();
        $parameterRealValues = null;
        $metaArgs = array_merge($metaArgs, $this->getMetaArgs($reflectionMethod));

        foreach ($parameters as $parameter) {
            if (!$parameter instanceof ReflectionParameter) continue;

            // when the parameter has a meta value
            if (isset($metaArgs[$parameter->getName()])) {
                $value = $metaArgs[$parameter->getName()];
                
                if (strpos(needle: '@', haystack: $value) === 0) {
                    $value = $this->getParameter(substr($value, 1));
                }

                if (!$parameter->getType()->isBuiltin()) {
                    $value = $this->loadObject($value);
                }

                $parameterRealValues[$parameter->getPosition()] = $value;
                
                continue;
            }

            if ($parameter->getType()->isBuiltin() === true) {
                throw new ParameterNotFoundException('The parameter ' . $parameter->getName() . ' is not explicitly defined');
            }

            $parameterRealValues[$parameter->getPosition()] = $this->loadObject((string) $parameter->getType());
        }

        return $parameterRealValues;
        return $parameterRealValues;
    }

    private function getMetaArgs(\ReflectionClass | \ReflectionMethod $reflection): array
    {
        $serviceMetas = $reflection->getAttributes(Service::class);
        $args = [];

        foreach ($serviceMetas as $serviceMeta) {
            $serviceMeta = $serviceMeta->newInstance();
            if (!$serviceMeta instanceof Service) continue;
            $args = array_merge($args, $serviceMeta->getArgs());
        }

        return $args;
    }

    private function getParameter(string $key): mixed
    {
        $ids = explode('.', $key);
        
        $value = &$this->parameters;
        
        while(count($ids)) {
            $id = array_shift($ids);
            if (!isset($value[$id])) throw new ParameterNotFoundException('The parameter @' . $key . ' is not found in the configuration');

            $value = &$value[$id];
        }

        return $value;
    }
}