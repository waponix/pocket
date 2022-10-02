<?php
namespace src\Pocket;

use src\Pocket\ClassIterator\ClassIterator;
use src\Pocket\Exception\ClassNotFoundException;
use src\Pocket\Exception\FileNotFoundException;

class Pocket
{
    const CONSTRUCTOR = '__construct';
    const PARAM_PREFIX = '@';

    private $classInstances = [];
    private $parameterSource = null;
    private $serviceMapping = null;
    private $serviceMap = null;
    private $parameters = null;
    private $parameterShortLinks = [];
    private static $instance;

    private function __construct()
    {
    }

    /**
     * @return Pocket
     */
    public static function getInstance() : Pocket
    {
        if (self::$instance instanceof Pocket) return self::$instance;
        self::$instance = new self();
        self::$instance->classInstances[self::class] = self::$instance;
        return self::$instance;
    }

    public static function configure(array $configuration)
    {
        $pocket = self::getInstance();

        if (isset($configuration['parameterSource']) && is_string($configuration['parameterSource'])) {
            $pocket->parameterSource = $configuration['parameterSource'];
        }

        if (isset($configuration['serviceMapping']) && is_string($configuration['serviceMapping'])) {
            $pocket->serviceMapping = $configuration['serviceMapping'];
        }
    }

    public function getParameter(?string $id)
    {
        if (!file_exists($this->parameterSource)) {
            return null;
        }

        if ($this->parameters === null) {
            $this->parameters = json_decode(file_get_contents($this->parameterSource), true);
        }

        return $this->findParameter($id);
    }

    /**
     * @param string|null $ogId
     * @param string|null $id
     * @param null $input
     * @return array|mixed|string
     */
    private function findParameter(?string $id = null)
    {
        if ($id === null || empty($id)) {
            // when there is no id provided, return the whole parameter
            return $this->parameters;
        }

        if (isset($this->parameterShortLinks[$id])) {
            // cached parameters are returned instantly
            return $this->parameterShortLinks[$id];
        }

        $stringId = $id;
        $id = explode('.', $id);
        $key = array_shift($id);

        if (!isset($this->parameters[$key])) {
            // return null value for non existing keys
            return null;
        }

        $parameter = &$this->parameters[$key];

        while (count($id) > 0) {
            $key = array_shift($id);
            if (!isset($parameter[$key])) {
                return null;
            }

            $parameter = &$parameter[$key];
        }

        $this->parameterShortLinks[$stringId] = &$parameter;

        return $parameter;
    }

    /**
     * @param string $class
     * @return mixed
     * @throws ClassNotFoundException
     * @throws FileNotFoundException
     */
    public function get(string $class)
    {
        if (!file_exists($this->serviceMapping)) {
            return null;
        }

        if ($this->serviceMap === null) {
            $this->serviceMap = json_decode(file_get_contents($this->serviceMapping), true);
        }

        if (!isset($this->serviceMap[$class])) {
            return null;
        }

        $classFile = str_replace('\\', '/', $class) . '.php';
        if (!file_exists($classFile)) {
            throw new FileNotFoundException('The class file ' . $class . ' does not exist');
        }

        return $this->loadObject($class);
    }

    /**
     * @param string $class
     * @return mixed
     * @throws \ReflectionException
     */
    private function loadObject(string $class)
    {
        if (isset($this->classInstances[$class])) return $this->classInstances[$class];

        $metaData = $this->serviceMap[$class];

        // when class exist in metaData, use it instead
        if (isset($metaData['class']) && !empty($metaData['class'])) $class = $metaData['class'];

        // Decided to use iterator because it is much faster than recursive, although the ClassIterator itself has recursive functionality
        // but is only minimal because it only need to get the classes and stack them accordingly
        // also added caching to reduce memory usage and add additional optimization
        // this also makes it that the object loaded is the same instance
        // TODO: add option to load separate instance of an object
        $classIterator = new ClassIterator($class);
        foreach ($classIterator as $c) {
            $reflectionMethod = null;

            if (method_exists($c, Pocket::CONSTRUCTOR)) {
                $reflectionMethod = new \ReflectionMethod($c, Pocket::CONSTRUCTOR);
                $reflectionParameters = $reflectionMethod->getParameters();
            } else {
                $reflectionClass = new \ReflectionClass($c);
                $this->classInstances[$c] = $reflectionClass->newInstance();
                continue;
            }

            // build class arguments
            $arguments = [];
            foreach ($reflectionParameters as $parameter) {
                if (!$parameter instanceof \ReflectionParameter) continue;

                if (isset($metaData['parameters']) && in_array($parameter->getName(), array_keys($metaData['parameters']))) {
                    // when argument name matches a parameter in meta data
                    if (strstr($metaData['parameters'][$parameter->getName()], Pocket::PARAM_PREFIX)) {
                        // check if value needs to be taken from parameter
                        $id = trim(str_replace(Pocket::PARAM_PREFIX, '', $metaData['parameters'][$parameter->getName()]));

                        $arguments[$parameter->getPosition()] = $this->getParameter($id);

                        continue;
                    }

                    if (!$parameter->getType()->isBuiltin() && isset($this->classInstances[$metaData['parameters'][$parameter->getName()]])) {
                        // when specific class is set for a parameter, try to load it as well
                        $arguments[$parameter->getPosition()] = $this->loadObject($metaData['parameters'][$parameter->getName()]);
                        continue;
                    }
                }

                if (!$parameter->getType()->isBuiltin() && isset($this->classInstances[$parameter->getType()->__toString()])) {
                    // if object already instantiated, use it
                    $arguments[$parameter->getPosition()] = $this->classInstances[$parameter->getType()->__toString()];
                    continue;
                }
            }

            $reflectionClass = $reflectionMethod->getDeclaringClass();

            $this->classInstances[$c] = $reflectionClass->newInstanceArgs($arguments);
        }

        return $this->classInstances[$class];
    }
}