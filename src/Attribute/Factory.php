<?php
namespace Waponix\Pocket\Attribute;

use Waponix\Pocket\Attribute\Interface\FactoryAttributeInterface;

class Factory implements FactoryAttributeInterface
{
    public function __construct(
        private readonly string $class,
        private readonly string $method,
        private readonly array $args = [],
    )
    {
        
    }
    
    public function getClass(): string
    {
        return $this->class;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getArgs(): array
    {
        return $this->args;
    }
}