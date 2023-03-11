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
    CONST CACHE_FILE = './tests/cachefiletest';

    public function testCacheFileShouldExist()
    {
        new Pouch(self::CACHE_FILE);

        $this->assertFileExists(self::CACHE_FILE);
    }

    public function testShouldWriteToFileAccordingly()
    {
        $this->assertTrue(unlink(self::CACHE_FILE));

        $pouch = new Pouch(self::CACHE_FILE);
        $timestamp = filemtime(self::CACHE_FILE);
        $this->assertSame($timestamp, filemtime(self::CACHE_FILE));

        $person = $pouch->get(Person::class);
        $this->assertNotInstanceOf(Person::class, $person);
        
        $person = new Person;
        sleep(1);
        $pouch->add($person);

        $person = $pouch->get(Person::class);
        $this->assertInstanceOf(Person::class, $person);

        clearstatcache(true, self::CACHE_FILE);
        $this->assertNotSame($timestamp, filemtime(self::CACHE_FILE));

        $timestamp = filemtime(self::CACHE_FILE);
        sleep(1);
        $pouch->add($person);

        $person = $pouch->get(Person::class);
        $this->assertInstanceOf(Person::class, $person);

        clearstatcache(true, self::CACHE_FILE);
        $this->assertSame($timestamp, filemtime(self::CACHE_FILE));
    }

    public function testShouldBeAbleToGetDataFromCache()
    {
        $pouch = new Pouch(self::CACHE_FILE);
        
        $person = new Person;
        $pouch->add($person);

        $person = $pouch->get(Person::class);
        $this->assertInstanceOf(Person::class, $person);

        touch(__FILE__); // cause a mismatch in file timetamp
        $person = $pouch->get(Person::class);
        $this->assertNull($person);
    }
}