<?php
namespace Waponix\Pocket\Dummy;

class PersonFactory
{
    public static function createBob(string $name, int $age, string $gender, string $email): Bob
    {
        return new Bob(name: $name, age: $age, gender: $gender, email: $email);
    }
}