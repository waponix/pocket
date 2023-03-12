<?php

use PHPUnit\Framework\TestCase;
use Pocket\Exception\ClassNotFoundException;
use Pocket\Pocket;

class PocketTest extends TestCase
{
    public function testShouldThrowClassNotFoundException()
    {
        $this->expectException(ClassNotFoundException::class);

        $pocket = new Pocket;

        $pocket->get('Unknown');
    }

    public function testShouldBeAbleToGetTheObject()
    {
        $pocket = new Pocket;

        $vehicle = $pocket->get(Vehicle::class);

        $this->assertInstanceOf(Vehicle::class, $vehicle);
    }
}

class Person
{
}

class Vehicle
{
    public function __construct(
        public Person $owner
    )
    {}
}