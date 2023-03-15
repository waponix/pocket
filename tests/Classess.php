<?php

use Waponix\Pocket\Attribute\Service;

class Person
{
}

#[Service(args: ['name' => '@person.john.name', 'age' => '@person.john.age', 'gender' => '@person.john.gender', 'email' => '@person.john.email'])]
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

#[Service(args: ['name' => '@person.jane.name', 'age' => '@person.jane.age', 'gender' => '@person.jane.gender', 'email' => '@person.jane.email'])]
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