<?php
namespace Pocket;

use Exception;
use Pocket\Exception\FolderNotFoundException;
use Pocket\Exception\FolderNotWritableException;
use ReflectionClass;

class Pouch
{
    CONST EOL = "\n";

    private string $format = '%s.%d.%s' . self::EOL;
    private string $cacheFile = '';
    private array $cache = [];

    public function __construct(string $cacheFile)
    {
        $this->cacheFile = $cacheFile;
        $this
            ->createFile()
            ->loadCache();
    }

    /**
     * store the data into cache giving an id
     */
    public function add(object $object): Pouch
    {
        $reflectionClass = new ReflectionClass($object);
        $id = md5($reflectionClass->getName());
        $timestamp = filemtime($reflectionClass->getFileName());

        if (isset($this->cache[$id]) && $this->get($id)[0] === $timestamp) {
            return $this; // no need to update cache
        }

        $this->cache[$id] = [
            $timestamp,
            $object
        ];

        $object = base64_encode(serialize($object));

        $cache = fopen($this->cacheFile, 'w');
        $line = sprintf($this->format, $id, $timestamp, $object);
        fputs($cache, $line, strlen($line));
        fclose($cache);

        return $this;
    }

    public function get(string $class)
    {
        $id = md5($class);
        if (isset($this->cache[$id])) {
            return $this->cache[$id][1];
        }

        return null;
    }

    private function loadCache(): mixed
    {
        $cache = fopen($this->cacheFile, 'r');
        while (!feof($cache)) {
            $line = fgets($cache);
            $data = explode('.', $line);
            if (count($data) === 4) {
                continue;
            }

            $object = base64_decode($data[2]);
            if ($object === false) {
                continue;
            }

            $this->cache[$data[0]] = [
                $data[1],
                unserialize($object)
            ];
        }

        return $this;
    }

    private function createFile()
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