<?php

use PHPUnit\Framework\TestCase;
use Pocket\Pouch;

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

        // person
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

        // vechicle
        $vehicle = $pouch->get(Vehicle::class);
        $this->assertNotInstanceOf(Vehicle::class, $vehicle);
        
        $vehicle = new Vehicle($person);
        sleep(1);
        $pouch->add($vehicle);

        $vehicle = $pouch->get(Vehicle::class);
        $this->assertInstanceOf(Vehicle::class, $vehicle);

        clearstatcache(true, self::CACHE_FILE);
        $this->assertNotSame($timestamp, filemtime(self::CACHE_FILE));

        $timestamp = filemtime(self::CACHE_FILE);
        sleep(1);
        $pouch->add($vehicle);

        $vehicle = $pouch->get(Vehicle::class);
        $this->assertInstanceOf(Vehicle::class, $vehicle);

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

        $vehicle = new Vehicle($person);
        $pouch->add($vehicle);

        $vehicle = $pouch->get(Vehicle::class);
        $this->assertInstanceOf(Vehicle::class, $vehicle);

        sleep(1);
        touch(__FILE__); // cause a mismatch in file timetamp

        $person = $pouch->get(Person::class);
        $this->assertInstanceOf(Person::class, $person);

        $vehicle = $pouch->get(Vehicle::class);
        $this->assertInstanceOf(Vehicle::class, $vehicle);
    }
}

class Person
{
    public string $name = '';
    public int $age = 0;
    public string $gender = '';
}

class Vehicle
{
    public function __construct(
        public Person $owner,
        public readonly string $model = '',
        public readonly string $color = '',
        public readonly string $manufacturer = '',
        public readonly string $type = '',
        public readonly string $dimension = '',
        public readonly string $fuelCapacity = ''
    )
    {
        
    }
}