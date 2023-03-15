<?php
namespace Waponix\Pocket;

use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use Waponix\Pocket\Attribute\Service;
use Waponix\Pocket\Exception\ParameterNotFoundException;
use Waponix\Pocket\Iterator\ClassCollector;

class Pocket
{
    private readonly Pouch $pouch;
    private array $parameters = [];
    private array $classMeta = [];

    public function __construct(array $parameters = [])
    {
        $this->pouch = new Pouch('./src/pocketcache');
        $this->parameters = $parameters;
    }
    
    public function &get(string $class): ?object
    {
        return $this->loadObject($class);
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
            $parameters = $reflectionClass->getMethod('__construct')?->getParameters();
            $parameterRealValues = [];
            $metaArgs = $this->getMetaArgs($reflectionClass);

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
        } catch (ReflectionException $exception) {
            $parameterRealValues = null;
        }

        return $parameterRealValues;
        return $parameterRealValues;
    }

    private function getMetaArgs(\ReflectionClass $reflectionClass): array
    {
        $serviceMetas = $reflectionClass->getAttributes(Service::class);
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