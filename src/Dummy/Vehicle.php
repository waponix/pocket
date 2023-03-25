<?php
namespace Waponix\Pocket\Dummy;

class Vehicle
{
    public function __construct(
        public Person $owner,
        public Manufacturer $manufacturer
    )
    {
        
    }
}