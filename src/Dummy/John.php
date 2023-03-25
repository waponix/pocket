<?php
namespace Waponix\Pocket\Dummy;

use Waponix\Pocket\Attribute\Service;

#[Service(
    args: [
        'name' => '@person.john.name', 
        'age' => '@person.john.age', 
        'gender' => '@person.john.gender', 
        'email' => '@person.john.email'
    ],
    tag: 'person'
)]
class John extends Person
{

}