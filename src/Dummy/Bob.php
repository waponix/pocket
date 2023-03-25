<?php
namespace Waponix\Pocket\Dummy;

use Waponix\Pocket\Attribute\Service;
use Waponix\Pocket\Attribute\Factory;

#[Service(
    factory: new Factory(
        class: PersonFactory::class,
        method: 'createBob',
        args: [
            'name' => '@person.bob.name',
            'age' => '@person.bob.age',
            'gender' => '@person.bob.gender',
            'email' => '@person.bob.email'
        ],
    ),
    tag: 'person',
)]
class Bob extends Person {
    public function __construct(
        public readonly ?string $name,
        public readonly ?int $age,
        public readonly ?string $gender,
        public readonly ?string $email
    )
    {
        
    }
}