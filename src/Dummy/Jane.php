<?php
namespace Waponix\Pocket\Dummy;

use Waponix\Pocket\Attribute\Service;

#[Service(
    args: [
        'name' => '@person.jane.name', 
        'age' => '@person.jane.age', 
        'gender' => '@person.jane.gender', 
        'email' => '@person.jane.email'
    ],
    tag: 'person'
)]
class Jane extends Person
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?int $age,
        public readonly ?string $gender,
        public readonly ?string $email
    )
    {
    }
}