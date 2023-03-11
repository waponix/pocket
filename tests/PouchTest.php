<?php

use PHPUnit\Framework\TestCase;
use Pocket\Pouch;

class Person
{
    public string $name = '';
    public int $age = 0;
    public string $gender = '';
}

class PouchTest extends TestCase
{
    CONST CACHE_FILE = './cachefiletest';

    public function testCacheFileShouldExist()
    {
        $cacher = new Pouch(self::CACHE_FILE);

        $this->assertFileExists(self::CACHE_FILE);
    }

    public function testShouldWriteToFileAccordingly()
    {
        $johnDoe = new Person;

        $pouch = new Pouch(self::CACHE_FILE);

        $timestamp = filemtime(self::CACHE_FILE);
        $pouch->add($johnDoe);

        $this->assertNotSame($timestamp, filemtime(self::CACHE_FILE));
    }

    public function testShouldBeAbleToGetDataFromCache()
    {
        $pouch = new Pouch(self::CACHE_FILE);
        $person = $pouch->get(Person::class);

        $this->assertInstanceOf(Person::class, $person);
    }
}