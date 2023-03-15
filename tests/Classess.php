<?php
class Person
{
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