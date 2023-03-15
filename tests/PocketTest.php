<?php
include_once __DIR__ . '/Classess.php';

use PHPUnit\Framework\TestCase;
use Waponix\Pocket\Exception\ClassNotFoundException;
use Waponix\Pocket\Pocket;

class PocketTest extends TestCase
{
    private array $parameters = [
        'person' => [
            'john' => [
                'name' => 'John Doe',
                'age' => 22,
                'gender' => 'male',
                'email' => 'johndoe@mailme.com'
            ],
            'jane' => [
                'name' => 'Jane Doe',
                'age' => 20,
                'gender' => 'female',
                'email' => 'janedoe@mailme.com'
            ]
        ]
    ];

    public function testShouldThrowClassNotFoundException()
    {
        $this->expectException(ClassNotFoundException::class);

        $pocket = new Pocket;

        $pocket->get('Unknown');
    }

    public function testShouldBeAbleToGetTheObject()
    {
        $pocket = new Pocket;
        $pocket->setParameters($this->parameters);

        // $vehicle = $pocket->get(Vehicle::class);
        // $this->assertInstanceOf(Vehicle::class, $vehicle);
        // $this->assertInstanceOf(Person::class, $vehicle->owner);

        $person = $pocket->get(Person::class);
        $this->assertInstanceOf(Person::class, $person);

        // $this->assertSame($person, $vehicle->owner);
    }

    public function testShouldBeJohn()
    {
        $pocket = new Pocket;
        $pocket->setParameters($this->parameters);

        $person = $pocket->get(John::class);

        $this->assertInstanceOf(Person::class, $person);
        $this->assertInstanceOf(John::class, $person);
        $this->assertSame($this->parameters['person']['john']['name'], $person->name);
        $this->assertSame($this->parameters['person']['john']['age'], $person->age);
        $this->assertSame($this->parameters['person']['john']['gender'], $person->gender);
        $this->assertSame($this->parameters['person']['john']['email'], $person->email);

        $vehicle = $pocket->get(JohnVehicle::class);
        $this->assertInstanceOf(Vehicle::class, $vehicle);
        $this->assertInstanceOf(JohnVehicle::class, $vehicle);
        $this->assertInstanceOf(Person::class, $vehicle->owner);
        $this->assertInstanceOf(John::class, $vehicle->owner);
    }

    public function testShouldBeJane()
    {
        $pocket = new Pocket;
        $pocket->setParameters($this->parameters);

        $person = $pocket->get(John::class);

        $this->assertInstanceOf(Person::class, $person);
        $this->assertInstanceOf(John::class, $person);
        $this->assertSame($this->parameters['person']['john']['name'], $person->name);
        $this->assertSame($this->parameters['person']['john']['age'], $person->age);
        $this->assertSame($this->parameters['person']['john']['gender'], $person->gender);
        $this->assertSame($this->parameters['person']['john']['email'], $person->email);
    }

    // public function testShouldHaveMetaData()
    // {
    //     $pocket = new Pocket;

    //     $pocket->setParameters([
    //         'person' => [
    //             'john' => [
    //                 'name' => 'John Doe',
    //                 'age' => 22,
    //                 'gender' => 'male',
    //                 'email' => 'johndoe@mailme.com'
    //             ]
    //         ]
    //     ]);

    //     $reflectionClass = new ReflectionClass(John::class);
    //     $metaArgs = $pocket->getMetaArgs($reflectionClass);

    //     $this->assertNotEmpty($metaArgs);

    //     $this->assertSame($pocket->getParameter(substr($metaArgs['name'], 1)), 'John Doe');
    // }
}