<?php
namespace Pocket;

use Pocket\Exception\FolderNotFoundException;
use Pocket\Exception\FolderNotWritableException;
use Pocket\PouchScanner;
use ReflectionClass;

/**
 * TODO:    1.) Develop a mechanism that will automatically remove unused cache data from file
 */
class Pouch
{
    CONST EOL = "\n";

    private readonly string $lineFormat;
    private array $cache = [];

    public function __construct(public readonly string $cacheFile)
    {
        $this->lineFormat = '%s.%d.%s.%s' . self::EOL;
        $this->createCacheFile();
    }

    /**
     * @var object $object
     * store the data into cache giving an id
     */
    public function add(object $object): Pouch
    {
        $reflectionClass = new ReflectionClass($object::class);

        $id = md5($reflectionClass->getName());

        if ($this->loadAndValidate($id)) {
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
        fputs($cache, $line, strlen($line));
        fclose($cache);

        return $this;
    }

    /**
     * @var string $class
     * @return object|null
     */
    public function &get(string $class): ?object
    {
        $id = md5($class);

        if ($this->loadAndValidate($id)) {
            return $this->cache[$id][2];
        }

        return null;
    }

    /**
     * @return void
     */
    public function clearCache()
    {
        $this->createCacheFile();
        file_put_contents($this->cacheFile, '');
    }

    private function loadAndValidate(string $id): bool
    {
        $data = $this->cache[$id] ?? false;
        // try to load data from cache
        if ($data === false) {
            $data = &$this->loadCacheFromId($id);    
        }

        if ($data === null) return false;

        $this->cache[$id] = &$data;

        if (count($data) != 3) return false;

        $reflectionClass = new ReflectionClass($data[2]);
        $classFile = $reflectionClass->getFileName();

        clearstatcache(true, $classFile);
        if (filemtime($classFile) > $data[0] && md5_file($classFile) !== $data[1]) {
            return false;
        }

        return true;
    }

    private function &loadCacheFromId(string $id): ?array
    {
        $pouchScanner = new PouchScanner($this->cacheFile);

        foreach ($pouchScanner as $line) {
            // check if the id is found at the first position
            if (strpos(needle: "$id.", haystack: $line) !== 0) continue;

            $data = explode('.', $line);
            if (count($data) != 4) {
                continue;
            }

            $object = base64_decode($data[3]);
            if ($object === false) {
                continue;
            }

            return [
                (int) $data[1],
                $data[2],
                unserialize($object)
            ];
        }

        return null;
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