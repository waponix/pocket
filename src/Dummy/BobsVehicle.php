<?php
namespace Waponix\Pocket\Dummy;

use Waponix\Pocket\Attribute\Service;

#[Service(
    args: [
        'owner' => Bob::class,
        'manufacturer' => Suzuki::class,
    ],
    tag: 'vehicle'
)]
class BobsVehicle extends Vehicle {
    
}