<?php
namespace Waponix\Pocket\Dummy;

use Waponix\Pocket\Attribute\Service;

#[Service(
    args: ['owner' => John::class],
    tag: 'vehicle'
)]
class JohnVehicle extends Vehicle
{

}