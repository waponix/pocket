<?php

use Waponix\Pocket\Attribute\Service;
use Waponix\Pocket\Attribute\Factory;

class Person
{
    const ID = 'person';

    public readonly string $name;
    public readonly int $age;
    public readonly string $gender;

    public function getName()
    {
        return $this->name;
    }

    private function getAge()
    {
        return $this->age;
    }

    protected function getGender()
    {
        return $this->gender;
    }

    public static function getId(): string
    {
        return self::ID;
    }
}

#[Service(
    args: [
        'name' => '@person.john.name', 
        'age' => '@person.john.age', 
        'gender' => '@person.john.gender', 
        'email' => '@person.john.email'
    ]
)]
class John extends Person
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly string $gender,
        public readonly string $email
    )
    {
        
    }
}

#[Service(args: ['owner' => John::class])]
class JohnVehicle extends Vehicle
{

}

#[Service(
    args: [
        'name' => '@person.jane.name', 
        'age' => '@person.jane.age', 
        'gender' => '@person.jane.gender', 
        'email' => '@person.jane.email'
    ]
)]
class Jane extends Person
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly string $gender,
        public readonly string $email
    )
    {
        
    }
}

class Manufacturer
{
    public function __construct(
        public Person $ceo
    )
    {}
}

class Vehicle
{
    public function __construct(
        public Person $owner,
        public Manufacturer $manufacturer
    )
    {
        
    }
}

#[Service(
    factory: new Factory(
        class: PersonFactory::class,
        method: 'createBob',
        args: [
            'name' => '@person.bob.name',
            'age' => '@person.bob.age',
            'gender' => '@person.bob.gender',
            'email' => '@person.bob.email'
        ]
    )
)]
class Bob extends Person {
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly string $gender,
        public readonly string $email
    )
    {
        
    }
}

#[Service(
    args: [
        'ceo' => John::class
    ]
)]
class Suzuki extends Manufacturer
{

}

#[Service(
    args: [
        'owner' => Bob::class,
        'manufacturer' => Suzuki::class,
    ]
)]
class BobsVehicle extends Vehicle {
    
}

class PersonFactory
{
    public static function createBob(string $name, int $age, string $gender, string $email): Bob
    {
        return new Bob(name: $name, age: $age, gender: $gender, email: $email);
    }
}