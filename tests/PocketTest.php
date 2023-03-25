<?php
include_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Waponix\Pocket\Dummy\Bob;
use Waponix\Pocket\Dummy\BobsVehicle;
use Waponix\Pocket\Dummy\Jane;
use Waponix\Pocket\Dummy\John;
use Waponix\Pocket\Dummy\JohnVehicle;
use Waponix\Pocket\Dummy\Manufacturer;
use Waponix\Pocket\Dummy\Person;
use Waponix\Pocket\Dummy\Suzuki;
use Waponix\Pocket\Dummy\Vehicle;
use Waponix\Pocket\Exception\ClassException;
use Waponix\Pocket\Exception\ClassNotFoundException;
use Waponix\Pocket\Exception\MethodNotFoundException;
use Waponix\Pocket\Exception\PocketConfigurationException;
use Waponix\Pocket\Pocket;

class PocketTest extends TestCase
{
    private ?Pocket $pocket = null;

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

    private function getPocket(): Pocket
    {
        if ($this->pocket instanceof Pocket) return $this->pocket;

        $this->pocket = new Pocket(
            root: __DIR__ . '/../src', 
            parameters: $this->parameters
        );

        return $this->pocket;
    }

    public function testShouldThrowClassNotFoundException()
    {
        $this->expectException(ClassNotFoundException::class);

        $pocket = $this->getPocket();

        $pocket->get('Unknown');
    }

    public function testShouldBeAbleToGetTheObject()
    {
        $pocket = $this->getPocket();

        $person = $pocket->get(Person::class);
        $this->assertInstanceOf(Person::class, $person);
    }

    public function testShouldBeJohn()
    {
        $pocket = $this->getPocket();

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
        $pocket = $this->getPocket();

        $person = $pocket->get(Jane::class);

        $this->assertInstanceOf(Person::class, $person);
        $this->assertInstanceOf(Jane::class, $person);
        $this->assertSame($this->parameters['person']['jane']['name'], $person->name);
        $this->assertSame($this->parameters['person']['jane']['age'], $person->age);
        $this->assertSame($this->parameters['person']['jane']['gender'], $person->gender);
        $this->assertSame($this->parameters['person']['jane']['email'], $person->email);
    }

    public function testShouldBeAbleToGetJohnsName()
    {
        $pocket = $this->getPocket();

        $this->assertSame('John Doe', $pocket->invoke(John::class, 'getName'));
    }

    public function testShouldThrowClassException()
    {
        $pocket = $this->getPocket();

        $this->expectException(ClassException::class);
        $pocket->invoke(John::class, 'getAge');

        $this->expectException(ClassException::class);
        $pocket->invoke(John::class, 'getGender');
    }

    public function testShouldBeAbleToInvokeStaticMethod()
    {
        $pocket = $this->getPocket();

        $this->assertSame('person', $pocket->invoke(John::class, 'getId'));
    }

    public function testShouldBeAbleToCreateBobFromFactory()
    {
        $pocket = $this->getPocket();

        $person = $pocket->get(Bob::class);
        $this->assertInstanceOf(Bob::class, $person);
        $this->assertInstanceOf(Person::class, $person);

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

    public function testShouldBeAllPersonsInTag()
    {
        $pocket = $this->getPocket();

        $persons = $pocket->get('#person');

        $this->assertNotNull($persons);

        foreach ($persons as $person) {
            $this->assertInstanceOf(Person::class, $person);
        }
    }

    public function testShouldBeAllVehiclesInTag()
    {
        $pocket = $this->getPocket();

        $vehicles = $pocket->get('#vehicle');

        $this->assertNotNull($vehicles);

        foreach ($vehicles as $vehicle) {
            $this->assertInstanceOf(Vehicle::class, $vehicle);
        }
    }

    public function testShouldBeAllManufacturersInTag()
    {
        $pocket = $this->getPocket();

        $manufacturers = $pocket->get('#manufacturer');

        $this->assertNotNull($manufacturers);

        foreach ($manufacturers as $manufacturer) {
            $this->assertInstanceOf(Manufacturer::class, $manufacturer);
        }
    }

    public function testNonExistingTagShouldBeNull()
    {
        $pocket = $this->getPocket();

        $manufacturers = $pocket->get('#none');

        $this->assertNull($manufacturers);
    }

    public function testShouldNotBeAbleToLoadClassWithNoServiceAttribute()
    {
        $this->expectException(ClassException::class);

        $pocket = $this->getPocket();

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