<?php
namespace Pocket;

use Pocket\Exception\FolderNotFoundException;
use Pocket\Exception\FolderNotWritableException;
use ReflectionClass;

class Pouch
{
    CONST EOL = "\n";

    private readonly string $lineFormat;
    private array $cache = [];

    public function __construct(public readonly string $cacheFile)
    {
        $this->lineFormat = '%s.%d.%s.%s' . self::EOL;
        $this
            ->createCacheFile()
            ->loadDataFromCache();
    }

    /**
     * @var object $object
     * store the data into cache giving an id
     */
    public function add(object $object): Pouch
    {
        $reflectionClass = new ReflectionClass($object::class);

        $id = md5($reflectionClass->getName());

        if ($this->isDataValid($id)) {
            return $this; // no need to update cache
        }

        $classFile = $reflectionClass->getFileName();
        $hash = md5_file($classFile);
        clearstatcache(true, $classFile);
        $timestamp = filemtime($classFile);

        $this->cache[$id] = [
            $timestamp,
            $hash,
            $object
        ];

        $object = base64_encode(serialize($object));

        $cache = fopen($this->cacheFile, 'a');
        $line = sprintf($this->lineFormat, $id, $timestamp, $hash, $object);
        fwrite($cache, $line, strlen($line));
        fclose($cache);

        return $this;
    }

    /**
     * @var string $class
     * @return object|null
     */
    public function get(string $class): ?object
    {
        $id = md5($class);
        if ($this->isDataValid($id)) {
            return $this->cache[$id][2];
        }

        return null;
    }

    private function isDataValid(string $id): bool
    {
        $data = $this->cache[$id] ?? false;

        if ($data === false) return false;

        if (count($data) != 3) return false;

        $reflectionClass = new ReflectionClass($data[2]);
        $classFile = $reflectionClass->getFileName();

        clearstatcache(true, $classFile);
        if (filemtime($classFile) > $data[0] && md5_file($classFile) !== $data[1]) {
            return false;
        }

        return true;
    }

    private function loadDataFromCache(): mixed
    {
        $cache = fopen($this->cacheFile, 'r');
        while (!feof($cache)) {
            $line = fgets($cache);
            $data = explode('.', $line);
            if (count($data) != 4) {
                continue;
            }

            $object = base64_decode($data[3]);
            if ($object === false) {
                continue;
            }

            $this->cache[$data[0]] = [
                (int) $data[1],
                $data[2],
                unserialize($object)
            ];
        }

        fclose($cache);

        return $this;
    }

    private function createCacheFile(): Pouch
    {
        $dir = dirname($this->cacheFile);
        if (!file_exists($dir)) {
            throw new FolderNotFoundException('The directory ' . $dir . ' does not exist');
        }

        if (file_exists($dir) && !is_writable($dir)) {
            throw new FolderNotWritableException('No permission to write into this folder ' . $dir);
        }

        if (file_exists($this->cacheFile)) {
            return $this;
        }

        touch ($this->cacheFile);

        return $this;
    }
}