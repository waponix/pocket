# pocket
Inspired by Symfony's "service container". The aim is to be able to get tools/services faster by instantiating the objects and automatically inject the dependencies, like a magician pulling an object out of his pocket.

Pocket is currently being developed in `PHP 8`, and will take advantage of it's new features as much as possible (e.g. defining meta data using `Attributes`)

## Introduction
Classes in pocket are all considered as Services, you can pass any class name to pocket's get() method and it will try to load the object for you
> Note: you need to setup your own autoloading because pocket won't handle that for you
```php
<?php
use Waponix\Pocket\Pocket;

class Person()
{
}

$pocket = new Pocket();

$person = $pocket->get(Person::class);
```
Pocket will also automatically load any objects in the constructor arguments
```php
<?php
class Person()
{
}

class Vehicle()
{
  public function __construct(
    public readonly Person $owner
  )
  {
  }
}

$pocket = new Pocket();

$person = $pocket->get(Vehicle::class);
```
