<?php
namespace Waponix\Pocket\Iterator;

class FileReader implements \Iterator
{
    private $stream;
    private int $key = 0;

    public function __construct(string $file)
    {
        if (!file_exists($file)) {
            throw new \Exception('File ' . $file . ' does not exist');
        }
        $this->stream = fopen($file, 'r');
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
        $this->key += 1;
    }

    public function valid(): bool
    {
        $isValid = $this->stream !== false && !feof($this->stream);
        if (!$isValid && $this->stream !== false) fclose($this->stream); // try to close the file

        return $isValid;
    }

    public function key(): int
    {
        return $this->key;
    }

    public function end(): void
    {
        fclose($this->stream);
    }
}