
# pocket
Is a kind of service container, it considers all loadable classes inside a project as a service.

Pocket is currently being developed in `PHP 8`, and will take advantage of it's new features as much as possible.

## Introduction
Pocket considers all classes as service, you can pass any class name to pocket's get() method without doing any registration and it will try to load the object for you.
> Note: you need to setup your own autoloading because pocket won't handle that for you, pocket also only works with PSR-4 autoloading

Example:
```php
<?php
use Waponix\Pocket\Pocket;

class Person()
{
}

$pocket = new Pocket(root: '/var/myProject'); // assuming that all of your source code is found in the /var/myProject directory

$person = $pocket->get(Person::class); // will return an object instance of Person
```

Pocket will also automatically load any arguments that are expected to be an instance of a class in the constructor.

Example:
```php
<?php
class Person()
{
}

class Vehicle()
{
  public function __construct(
    public readonly Person $owner // this will be auto injected
  )
  {
  }
}

$pocket = new Pocket(root: '/var/myProject');

$person = $pocket->get(Vehicle::class);
```
In the example above, the argument `$owner` will be injected with an instance of class Person. In any case that class Person also has any argument that is excpected to be class instance, those will automatically be injected as well.

### **But what about other argument types?**
For other arguments, pocket cannot automatically inject its values and will throw an exception, but this problem can be solved by using Attribute.

## Using the Service Attribute
Pocket will try to load any object as long as it's arguments are loadable or are explicitly defined. Arguments that expects instance of a class are considered explicit because they can be autoloaded, but for other arguments like string, integer, etc. we will need to find a way how to define them, we will need to use the **Service Attribute** [(`Waponix\Pocket\Attribute\Service`)](./src/Attribute/Service.php "(`Waponix\Pocket\Attribute\Service`)").

> [Attribute class](http://https://www.php.net/manual/en/class.attribute.php "Attribute class") is a new feature in PHP 8 which is a native way of adding [Annotations](https://php-annotations.readthedocs.io/en/latest/UsingAnnotations.html "Annotations") in the code

Example:
```php
<?php
use Waponix\Pocket\Attribute\Service;

#[Service(
	args: [
		'method' => 'GET', // will map to $method
		'url' => 'localhost' // will map to $url
	]
)]
class Post()
{
	publc function __construct(
		public readonly string $method,
		public readonly string $url,
	)
	{
	
	}
}

$post = $pocket->get(Post::class);
```
Take note that to make this work we should make the key inside `args:` match the variable name in the constructor arguments (*$name* and *$url*), this way we are telling pocket where to exactly get the values for them.

> It is also possible to override the value for an argument that expects a class instance, as long as it is a **child** of the expected class (e.g class John instance of class Person)

## Parameters
The previous example shows how to define the argument value the directly in the code, but it is also possible to have a dataset of parameters and pocket will take the value from there.

First we need to set the parameters:
```php
use Waponix\Pocket\Pocket;

$pocket = new Pocket(
	root: '/var/myProject',
	parameters: [
		'post' => [
			'method' => 'GET',
			'url' => 'localhost'
		]
	]
);
```

Then we can target these values in the Service Attribute:
```php
#[Service(
    args: [
        'method' => '@post.method',
        'url' => '@post.url'
    ]
)]
class Post()
{
    publc function __construct(
        public readonly string $method,
        public readonly string $url,
    )
    {
    }
}
```
In the new example we are replacing the values with a parameter id indicated by `@`, this will then tell pocket to get the values from the parameter based on the id
> Notice that the id `@post.method` is equivalent to $parameters['post']['method']
## Invoke Methods
Pocket also allows to invoke class methods and automatically injects dependencies.

Let's say you have a controller class that has method that represents an action:

```php
class HomeController
{
	public function index(Request $request): Response
	{
		// do something with the $request and return a Response
		return new Response();
	}
}
```

you can invoke this method by doing the example below:
```php
$pocket->invoke(HomeController::class, 'index');
```
## Factory
If somehow your requirement needs to use factories for creating objects, then you can do this by defining a factory in the Service Attribute of the target class.

Example:
```php
use Waponix\Pocket\Attribute\Service;
use Waponix\Pocket\Attribute\Factory;

#[Service(
	factory: new Factory(
		class: PersonCreator::class,
		method: 'createBob'
	)
)]
class Bob extends PersonAbstract
{}
```
and your factory class would look something like:
```php
class PersonCreator
{
	public function createBob(): Person // concrete class
	{
		return new Bob();
	}
}
```
## Service Group
Another helpful tool is the ability to group services, these can be achieved  by defining a tag value in the Service Attribute.

Example:
```php
use Waponix\Pocket\Attribute\Service;

#[Service(tag: 'person')]
class John extends Person
{
}
```
Now John is will be grouped under the tag person and can be loadable by accessing the tag name `#person`:
```php
$persons = $pocket->get('#person');
```
> Note: that the returned value will be an array or null if that tag is not used

You can also define multiple tags for a service by just passing an array of string into the `tag` argument:
```php
use Waponix\Pocket\Attribute\Service;

#[Service(tag: ['person', 'employee', 'owner'])]
class John extends Person
{
}
```