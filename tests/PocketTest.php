<?php
include_once __DIR__ . '/Classess.php';

use PHPUnit\Framework\TestCase;
use Waponix\Pocket\Exception\ClassNotFoundException;
use Waponix\Pocket\Pocket;

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
        $this->assertInstanceOf(Person::class, $vehicle->owner);

        $person = $pocket->get(Person::class);
        $this->assertInstanceOf(Person::class, $person);

        // $this->assertSame($person, $vehicle->owner);
    }
}