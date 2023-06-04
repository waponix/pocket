<?php
namespace Waponix\Pocket\Attribute;

use Waponix\Pocket\Attribute\Interface\ServiceAttributeInterface;
/**
 * The Service Attribute:
 * explicitly define argument values to inject into the object when it is instantiated, 
 * use @ sign if the value needed is coming from Pocket->parameters (e.g @person.john.name = $parameters['person']['john']['name'])
 */
#[\Attribute]
class ServiceAttribute implements ServiceAttributeInterface
{
    public function __construct(
        private readonly array $args = [],
        private readonly ?FactoryAttribute $factory = null,
        private readonly null|string|array $tag = null,
    ) {

    }

    public function getArgs(): array
    {
        return $this->args;
    }

    public function getFactory(): ?FactoryAttribute
    {
        return $this->factory;
    }

    public function getTags(): null|string|array
    {
        return $this->tag;
    }
}