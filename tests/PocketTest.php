<?php
include_once __DIR__ . '/Classess.php';

use PHPUnit\Framework\TestCase;
use Waponix\Pocket\Exception\ClassException;
use Waponix\Pocket\Exception\ClassNotFoundException;
use Waponix\Pocket\Exception\MethodNotFoundException;
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
            ],
            'bob' => [
                'name' => 'Bob',
                'age' => 25,
                'gender' => 'male',
                'email' => 'bob@ong.com'
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

    public function testShouldBeAbleToGetJohnsName()
    {
        $pocket = new Pocket;
        $pocket->setParameters($this->parameters);

        $this->assertSame('John Doe', $pocket->invoke(John::class, 'getName'));
    }

    public function testShouldThrowClassException()
    {
        $pocket = new Pocket;
        $pocket->setParameters($this->parameters);

        $this->expectException(ClassException::class);
        $pocket->invoke(John::class, 'getAge');

        $this->expectException(ClassException::class);
        $pocket->invoke(John::class, 'getGender');
    }

    public function testShouldBeAbleToInvokeStaticMethod()
    {
        $pocket = new Pocket;
        $pocket->setParameters($this->parameters);

        $this->assertSame('person', $pocket->invoke(John::class, 'getId'));
    }

    public function testShouldBeAbleToCreateBobFromFactory()
    {
        $pocket = new Pocket;
        $pocket->setParameters($this->parameters);

        $person = $pocket->get(Bob::class);
        $this->assertInstanceOf(Bob::class, $person);
        $this->assertInstanceOf(Person::class, $person);

        for ($loop = 0; $loop < 1000; $loop++) {
            $vehicle = $pocket->get(BobsVehicle::class);
            $this->assertInstanceOf(Vehicle::class, $vehicle);
            $this->assertInstanceOf(BobsVehicle::class, $vehicle);

            $manufacturer = $vehicle->manufacturer;
            $this->assertInstanceOf(Manufacturer::class, $manufacturer);
            $this->assertInstanceOf(Suzuki::class, $manufacturer);

            $ceo = $manufacturer->ceo;
            $this->assertInstanceOf(Person::class, $ceo);
            $this->assertInstanceOf(John::class, $ceo);
            $this->assertSame($this->parameters['person']['john']['name'], $ceo->name);
            $this->assertSame($this->parameters['person']['john']['age'], $ceo->age);
            $this->assertSame($this->parameters['person']['john']['gender'], $ceo->gender);
            $this->assertSame($this->parameters['person']['john']['email'], $ceo->email);

            $person = $vehicle->owner;
            $this->assertInstanceOf(Bob::class, $person);
            $this->assertInstanceOf(Person::class, $person);
            $this->assertSame($this->parameters['person']['bob']['name'], $person->name);
            $this->assertSame($this->parameters['person']['bob']['age'], $person->age);
            $this->assertSame($this->parameters['person']['bob']['gender'], $person->gender);
            $this->assertSame($this->parameters['person']['bob']['email'], $person->email);
        }
    }

    public function testShouldNotBeAbleToLoadClassWithNoServiceAttribute()
    {
        $this->expectException(ClassException::class);

        $pocket = new Pocket();

        $pocket
            ->strictLoadingEnabled(true)
            ->get(Vehicle::class);
    }

    // public function testShouldHaveMetaData()
    // {
    //     $pocket = new Pocket;

    //     $pocket->setParameters($this->parameters);

    //     $reflectionClass = new ReflectionClass(John::class);
    //     $metaArgs = $pocket->getMetaArgs($reflectionClass);

    //     $this->assertNotEmpty($metaArgs);

    //     $this->assertSame($pocket->getParameter(substr($metaArgs['name'], 1)), 'John Doe');
    // }
}