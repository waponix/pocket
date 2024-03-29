<?php
namespace Waponix\Pocket;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Waponix\Pocket\Attribute\FactoryAttribute;
use Waponix\Pocket\Attribute\ServiceAttribute;
use Waponix\Pocket\Attribute\MiddlewareAttribute;
use Waponix\Pocket\Exception\ClassException;
use Waponix\Pocket\Exception\ClassNotFoundException;
use Waponix\Pocket\Exception\ParameterNotFoundException;
use Waponix\Pocket\Exception\PocketConfigurationException;
use Waponix\Pocket\Iterator\ClassCollection;
use Waponix\Pocket\Iterator\FileReader;

class Pocket
{
    const ID_TAG = '#';
    const ID_PARAM = '@';

    private static ?string $root;
    private static array $parameters;

    private readonly Pouch $pouch;
    private array $paramLinks = [];
    private array $tags = [];
    private bool $strictLoading = false;

    private static ?Pocket $instance = null;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (self::$instance instanceOf Pocket) {
            return self::$instance;
        }

        self::$instance = new self;
        self::$instance->init();

        return self::$instance;
    }

    // basically replaces the __construct
    private function init(): void
    {
        $this->pouch = new Pouch(__DIR__ . '/pocketcache');
        $this->pouch->add($this);
        $this->loadTaggedServices();
    }

    public static function setRoot(string $root): void
    {
        self::$root = $root;
    }

    public static function setParameters(array $parameters): void
    {
        self::$parameters = $parameters;
    }
    
    public function &get(string $id): mixed
    {
        if (stripos($id, self::ID_PARAM) === 0) {
            $tags = $this->getParameter(substr($id, 1));
            return $tags;
        }

        if (stripos($id, self::ID_TAG) === 0) {
            return $this->loadTag(substr($id, 1));
        }

        $object = $this->pouch->get($id);

        if ($object !== null) {
            $reflectionClass = new ReflectionClass($object);
            $this->evaluateReflection($reflectionClass);
        }

        $object = $object ?? $this->loadObject($id);
        return $object;
    }

    private function loadTag(string $id): array
    {
        return $this->tags[$id] ?? [];
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

        
        return $this->middleManage($class, $reflectionMethod, $args);
    }

    private function middleManage(string $class, ReflectionMethod $reflectionMethod, ?array $args = [])
    {
        $args = $this->collectParameters($reflectionMethod, $args);

        $object = null;
        if (!$reflectionMethod->isStatic()) {
            $object = $this->get($class);
        }

        // Handle middlewares
        $middlewareAttributes = $reflectionMethod->getAttributes(MiddlewareAttribute::class, 2);

        foreach ($middlewareAttributes as $attribute) {
            $attribute = $attribute->newInstance();
            $middlewares = $attribute->getMiddlewares();

            while (count($middlewares) > 0) {
                $middlewareSet = array_shift($middlewares);
                $middlewareClass = array_shift($middlewareSet);
                $middlewareMethods = $middlewareSet;

                foreach ($middlewareMethods as $middlewareMethod) {
                    $middlewareReflection = new ReflectionMethod($middlewareClass, $middlewareMethod);

                    $middlewareObject = null;
                    if (!$middlewareReflection->isStatic()) {
                        $middlewareObject = $this->get($middlewareClass);
                    }

                    if (!method_exists($middlewareClass, $middlewareMethod)) {
                        throw new ClassException('Class ' . $middlewareClass . ' does not have or has no public access to call method ' . $middlewareMethod . '()');
                    }

                    $middlewareArgs = [];
                    foreach ($middlewareReflection->getParameters() as $parameter) {
                        $argValue = null;

                        foreach($reflectionMethod->getParameters() as $mainParameter) {
                            if ($parameter->getName() === $mainParameter->getName()) {
                                $argValue = $args[$mainParameter->getPosition()];
                                continue;
                            }
                        }

                        if ($argValue === null) {
                            throw new ParameterNotFoundException('Parameter $' . $parameter->getName() . ' is not accessible called in ' . $middlewareClass . '::' . $middlewareMethod . ', middlewares can only access parameters that are explicitly defined in the main function');
                        }

                        $middlewareArgs[$parameter->getPosition()] = $argValue;
                    }

                    $runNext = match (true) {
                        !empty($middlewareArgs) => $middlewareReflection->invokeArgs($middlewareObject, $middlewareArgs),
                        empty($middlewareArgs) => $middlewareReflection->invoke($middlewareObject)
                    };

                    if (is_integer($runNext)) {
                        for ($skip = 1; $skip <= $runNext; $skip++) {
                            if (count($middlewares) <= 0) break;
                            array_shift($middlewares);
                        }
                    } else if ($runNext !== true) {
                        // if the current middleware didn't return true, this wil no longer execute the remaining middlewares
                        break(3);
                    }
                }
            }
        }

        return match (true) {
            is_array($args) => $reflectionMethod->invokeArgs($object, $args),
            $args === null => $reflectionMethod->invoke($object)
        };
    }

    public function strictLoadingEnabled(bool $flag): Pocket
    {
        $this->strictLoading = $flag;
        return $this;
    }

    private function loadTaggedServices(): void
    {
        if (self::$root === null) {
            throw new PocketConfigurationException('No root is configured');
        }

        if (!file_exists(self::$root)) {
            throw new PocketConfigurationException('Root ' . self::$root . ' does not exist');
        }

        $finder = new \RecursiveDirectoryIterator(self::$root);
        $finder->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($finder);
        $files = new \RegexIterator($iterator, '/\.php$/');

        foreach ($files as $file) {
            $fileFullPath = str_replace('\\', '/', implode('/', [$file->getPath(), $file->getFilename()]));
            $namespace = $this->getNamespaceFromFile($fileFullPath);
            if ($namespace === null) continue;

            $class = implode('\\', [$namespace, str_replace('.php', '', $file->getFilename())]);

            $reflectionClass = new ReflectionClass($class);
            $attributes = $reflectionClass->getAttributes(ServiceAttribute::class, 2);

            foreach ($attributes as $attribute) {
                $tags = $attribute->newInstance()->getTags();

                if ($tags === null) continue;

                $object = &$this->loadObject($class);

                if (is_string($tags)) {
                    $this->addToTag($tags, $object);
                } else if (is_array($tags)) {
                    foreach ($tags as $tag) {
                        $this->addToTag($tag, $object);
                    }
                }
            }
        }
    }

    private function addToTag(string $tagName, object &$object)
    {
        if (!isset($this->tags[$tagName])) {
            $this->tags[$tagName] = [];
        }

        $this->tags[$tagName][] = &$object;
    }

    private function getNamespaceFromFile(string $file)
    {
        $reader = new FileReader($file);

        $src = '';
        $namespace = null;
        foreach ($reader as $line) {
            if (trim($line) === '') continue;

            $src .= $line;

            $namespace = $this->scanNamespace($src);
            if ($namespace !== null) {
                return $namespace;
            }
        }
    }

    private function scanNamespace(string $src): ?string
    {
        $tokens = token_get_all($src);
    
        $isPHP = false;
        $isNamespace = false;
        $namespace = null;
        
        foreach ($tokens as $token) {
            if (!is_array($token) || count($token) < 3) continue;
            
            if (token_name($token[0]) === 'T_OPEN_TAG') {
                $isPHP = true;
            }

            if (!$isPHP) {
                return null;
            }

            if ($isNamespace === true && token_name($token[0]) === 'T_NAME_QUALIFIED') {
                $namespace = $token[1];
                break;
            } else if (token_name($token[0]) === 'T_NAMESPACE') {
                $isNamespace = true;
                continue;
            }
        }
    
        return $namespace;
    }

    private function &loadObject(string $targetClass): ?object
    {
        $classCollection = new ClassCollection($targetClass);

        foreach ($classCollection as $class) {
            $reflectionClass = new ReflectionClass($class);

            $this->evaluateReflection($reflectionClass);

            $factory = null;
            $metaArgs = $this->getMetaArgs($reflectionClass, $factory); // get the meta args from the class level
            $parameters = null;

            if ($factory instanceof FactoryAttribute) {
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

            if ($reflectionClass->isInternal() === true) {
                continue; // skip internal classes, this values should be defined explicitly
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
        // use array dismantling to merge array values
        $metaArgs = [...$metaArgs, ...$this->getMetaArgs($reflectionMethod)];

        foreach ($parameters as $parameter) {
            if (!$parameter instanceof ReflectionParameter) continue;

            // when the parameter has a meta value
            if (isset($metaArgs[$parameter->getName()])) {
                $value = $metaArgs[$parameter->getName()];
                
                if (is_string($value) && strpos(needle: self::ID_TAG, haystack: $value) === 0) {
                    // the value is a tag id
                    $value = $this->loadTag(substr($value, 1));
                } else if (is_string($value) && strpos(needle: self::ID_PARAM, haystack: $value) === 0) {
                    // the value is a parameter
                    $value = $this->getParameter(substr($value, 1));
                } else if (!$parameter->getType()->isBuiltin()) {
                    // the value could be a class try loading it
                    $value = $this->pouch->get($value) ?? $this->loadObject($value);
                }

                $parameterRealValues[$parameter->getPosition()] = $value;
                
                continue;
            }

            if ($parameter->getType()->isBuiltin() === true && $parameter->isOptional() === false) {
                throw new ParameterNotFoundException('Parameter ' . $parameter->getName() . ' is not explicitly defined');
            } else if ($parameter->getType()->isBuiltin() === true && $parameter->isOptional() === true) {
                // use the default value if no matching meta data found
                $parameterRealValues[$parameter->getPosition()] = $parameter->getDefaultValue(); 
                continue;
            }

            $parameterRealValues[$parameter->getPosition()] = $this->pouch->get((string) $parameter->getType()) ?? $this->loadObject((string) $parameter->getType()); // trust that the class is already in the cache
        }

        return $parameterRealValues;
    }

    private function getMetaArgs(\ReflectionClass | \ReflectionMethod $reflection, ?FactoryAttribute &$factory = null): array
    {
        $serviceMetas = $reflection->getAttributes(ServiceAttribute::class, 2);
        $args = [];

        foreach ($serviceMetas as $serviceMeta) {
            $serviceMeta = $serviceMeta->newInstance();
            if (!$serviceMeta instanceof ServiceAttribute) continue;
            $factory = $serviceMeta->getFactory() ?? $factory;
            $args = [...$args, ...$serviceMeta->getArgs()];
        }

        return $args;
    }

    private function getParameter(string $key): mixed
    {
        if (isset($this->paramLinks[$key])) return $this->paramLinks[$key];

        $ids = explode('.', $key);
        
        $value = self::$parameters;
        
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
            $isService = count($reflectionClass->getAttributes(ServiceAttribute::class, 2)) > 0;
            
            if ($isService === false) {
                throw new ClassException('Class ' . $reflectionClass->getName() . ' is not a registered service');
            }
        }
    }
}