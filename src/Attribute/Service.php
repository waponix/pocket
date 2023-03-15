<?php
namespace Waponix\Pocket\Attribute;

use Waponix\Pocket\Attribute\Interface\ServiceAttributeInterface;
/**
 * The Service Attribute:
 * explicitly define argument values to inject into the object when it is instantiated, 
 * use @ sign if the value needed is coming from Pocket->parameters (e.g @person.john.name = $parameters['person']['john']['name'])
 */
#[\Attribute]
class Service implements ServiceAttributeInterface
{
    public function __construct(
        private readonly array $args
    ) {

    }

    public function getArgs(): array
    {
        return $this->args;
    }
}