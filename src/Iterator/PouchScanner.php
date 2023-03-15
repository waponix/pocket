<?php
namespace Waponix\Pocket\Iterator;

/**
 * Iterator that will read the cache file from bottom to top
 */
class PouchScanner implements \Iterator
{
    private $stream;
    private int $key = 0;

    public function __construct(string $cacheFile)
    {
        $this->stream = fopen($cacheFile, 'r');
    }

    public function current(): mixed
    {
        return fgets($this->stream);
    }

    public function rewind(): void 
    {
        $this->key = 0;
    }

    public function next(): void
    {
        $this->key -= 1;
    }

    public function valid(): bool
    {
        $isValid = $this->stream !== false && fseek($this->stream, $this->key, SEEK_END) !== -1;
        if (!$isValid && $this->stream !== false) fclose($this->stream); // try to close the file

        return $isValid;
    }

    public function key(): int
    {
        return $this->key;
    }
}