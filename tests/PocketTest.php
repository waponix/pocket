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

Pocket::setRoot( __DIR__ . '/../src');
Pocket::setParameters([
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
]);

class PocketTest extends TestCase
{
    private function getPocket(): Pocket
    {
        return Pocket::getInstance();
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
        $this->assertSame($pocket->get('@person.john.name'), $person->name);
        $this->assertSame($pocket->get('@person.john.age'), $person->age);
        $this->assertSame($pocket->get('@person.john.gender'), $person->gender);
        $this->assertSame($pocket->get('@person.john.email'), $person->email);

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
        $this->assertSame($pocket->get('@person.jane.name'), $person->name);
        $this->assertSame($pocket->get('@person.jane.age'), $person->age);
        $this->assertSame($pocket->get('@person.jane.gender'), $person->gender);
        $this->assertSame($pocket->get('@person.jane.email'), $person->email);
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
        $this->assertSame($pocket->get('@person.john.name'), $ceo->name);
        $this->assertSame($pocket->get('@person.john.age'), $ceo->age);
        $this->assertSame($pocket->get('@person.john.gender'), $ceo->gender);
        $this->assertSame($pocket->get('@person.john.email'), $ceo->email);

        $person = $vehicle->owner;
        $this->assertInstanceOf(Bob::class, $person);
        $this->assertInstanceOf(Person::class, $person);
        $this->assertSame($pocket->get('@person.bob.name'), $person->name);
        $this->assertSame($pocket->get('@person.bob.age'), $person->age);
        $this->assertSame($pocket->get('@person.bob.gender'), $person->gender);
        $this->assertSame($pocket->get('@person.bob.email'), $person->email);
    }

    public function testShouldBeAllPersonsInTag()
    {
        $pocket = $this->getPocket();

        $persons = $pocket->get('#person');

        $this->assertIsArray($persons);

        foreach ($persons as $person) {
            $this->assertInstanceOf(Person::class, $person);
        }
    }

    public function testShouldBeAllVehiclesInTag()
    {
        $pocket = $this->getPocket();

        $vehicles = $pocket->get('#vehicle');

        $this->assertIsArray($vehicles);

        foreach ($vehicles as $vehicle) {
            $this->assertInstanceOf(Vehicle::class, $vehicle);
        }
    }

    public function testShouldBeAllManufacturersInTag()
    {
        $pocket = $this->getPocket();

        $manufacturers = $pocket->get('#manufacturer');

        $this->assertIsArray($manufacturers);

        foreach ($manufacturers as $manufacturer) {
            $this->assertInstanceOf(Manufacturer::class, $manufacturer);
        }
    }

    public function testShouldBeAbleToGetParameterValue()
    {
        $pocket = $this->getPocket();

        $johnsName = $pocket->get('@person.john.name');

        $this->assertIsString($johnsName);
        $this->assertSame('John Doe', $johnsName);

        $JanesName = $pocket->get('@person.jane.name');

        $this->assertIsString($JanesName);
        $this->assertSame('Jane Doe', $JanesName);

        $bobsName = $pocket->get('@person.bob.name');

        $this->assertIsString($bobsName);
        $this->assertSame('Bob', $bobsName);
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