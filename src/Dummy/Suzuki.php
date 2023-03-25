<?php
namespace Waponix\Pocket\Dummy;

use Waponix\Pocket\Attribute\Service;

#[Service(
    args: [
        'ceo' => John::class
    ],
    tag: 'manufacturer'
)]
class Suzuki extends Manufacturer
{

}