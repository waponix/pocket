<?php
namespace Waponix\Pocket;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Waponix\Pocket\Attribute\Factory;
use Waponix\Pocket\Attribute\Service;
use Waponix\Pocket\Exception\ClassException;
use Waponix\Pocket\Exception\ClassNotFoundException;
use Waponix\Pocket\Exception\ParameterNotFoundException;
use Waponix\Pocket\Iterator\ClassCollector;

class Pocket
{
    private readonly Pouch $pouch;
    private array $parameters = [];
    private array $paramLinks = [];
    private bool $strictLoading = false;

    public function __construct(array $parameters = [])
    {
        $this->pouch = new Pouch('./src/pocketcache');
        $this->parameters = $parameters;
    }
    
    public function &get(string $class): ?object
    {
        $object = $this->pouch->get($class);

        if ($object !== null) {
            $reflectionClass = new ReflectionClass($object);
            $this->evaluateReflection($reflectionClass);
        }

        return $object ?? $this->loadObject($class);
    }

    public function invoke(string $class, string $method, ?array $args = []): mixed
    {
        if (!class_exists($class)) {
            throw new ClassNotFoundException('Class ' . $class . ' does not exist');
        }

        if (!method_exists($class, $method)) {
            throw new ClassException('Class ' . $class . ' does not have or has no public access to call method ' . $method . '()');
        }

        $reflectionMethod = new ReflectionMethod($class, $method);
        
        if (!$reflectionMethod->isPublic()) {
            throw new ClassException('Class ' . $class . ' has no public access to call method ' . $method . '()');
        }

        $args = $this->collectParameters($reflectionMethod, $args);
        $object = null;
        if (!$reflectionMethod->isStatic()) {
            $object = $this->get($class);
        }

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

    public function strictLoadingEnabled(bool $flag): Pocket
    {
        $this->strictLoading = $flag;
        return $this;
    }

    private function &loadObject(string $targetClass): ?object
    {
        $classCollector = new ClassCollector($targetClass);

        foreach ($classCollector as $class) {
            $reflectionClass = new ReflectionClass($class);

            $this->evaluateReflection($reflectionClass);

            $factory = null;
            $metaArgs = $this->getMetaArgs($reflectionClass, $factory); // get the meta args from the class level
            $parameters = null;

            if ($factory instanceof Factory) {
                $class = $factory->getClass(); // alias the class with the factory class
                // try to get object in cache
                $object = $this->pouch->get($class);

                if (is_object($object) && get_class($object) === $class) {
                    // when found no need to do processing
                    continue;
                }

                $object = $this->invoke($class, $factory->getMethod(), $factory->getArgs());
                $this->pouch->add($object);
                continue;
            }
        
            $object = &$this->pouch->get($class);

            if (is_object($object) && get_class($object) === $class) {
                continue;
            }

            if (method_exists($class, '__construct')) {
                $reflectionMethod = $reflectionClass->getMethod('__construct');

                if (!$reflectionMethod->isPublic()) {
                    throw new ClassException('Method __construct of class ' . $class . ' is not accessible or not defined as public');
                }
                
                $parameters = $this->collectParameters($reflectionMethod, $metaArgs);
            }

            $object = match (true) {
                is_array($parameters) => $reflectionClass->newInstanceArgs($parameters),
                $parameters === null => $reflectionClass->newInstance()
            };

            $this->pouch->add($object);
        }

        return $this->pouch->get($targetClass);
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
                } else if (!$parameter->getType()->isBuiltin()) {
                    // the value could be a class try loading it
                    $value = $this->pouch->get($value) ?? $this->loadObject($value);
                }

                $parameterRealValues[$parameter->getPosition()] = $value;
                
                continue;
            }

            if ($parameter->getType()->isBuiltin() === true) {
                throw new ParameterNotFoundException('Parameter ' . $parameter->getName() . ' is not explicitly defined');
            }

            $parameterRealValues[$parameter->getPosition()] = $this->pouch->get((string) $parameter->getType()) ?? $this->loadObject((string) $parameter->getType()); // trust that the class is already in the cache
        }

        return $parameterRealValues;
    }

    private function getMetaArgs(\ReflectionClass | \ReflectionMethod $reflection, ?Factory &$factory = null): array
    {
        $serviceMetas = $reflection->getAttributes(Service::class);
        $args = [];

        foreach ($serviceMetas as $serviceMeta) {
            $serviceMeta = $serviceMeta->newInstance();
            if (!$serviceMeta instanceof Service) continue;
            $factory = $serviceMeta->getFactory() ?? $factory;
            $args = array_merge($args, $serviceMeta->getArgs());
        }

        return $args;
    }

    private function getParameter(string $key): mixed
    {
        if (isset($this->paramLinks[$key])) return $this->paramLinks[$key];

        $ids = explode('.', $key);
        
        $value = &$this->parameters;
        
        while(count($ids)) {
            $id = array_shift($ids);
            if (!isset($value[$id])) throw new ParameterNotFoundException('Parameter @' . $key . ' is not found in the configuration');

            $value = &$value[$id];
        }

        $this->paramLinks[$key] = &$value;

        return $value;
    }

    private function evaluateReflection(\ReflectionClass $reflectionClass)
    {
        if ($this->strictLoading === true) {
            $isService = count($reflectionClass->getAttributes(Service::class)) > 0;
            
            if ($isService === false) {
                throw new ClassException('Class ' . $reflectionClass->getName() . ' is not a registered service');
            }
        }
    }
}