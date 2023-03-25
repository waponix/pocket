<?php
namespace Waponix\Pocket\Dummy;

class Person
{
    const ID = 'person';
    
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?int $age = null,
        public readonly ?string $gender = null,
        public readonly ?string $email = null
    ) {}

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