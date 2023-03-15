<?php
namespace Waponix\Pocket\Attribute;

use Waponix\Pocket\Attribute\Interface\ServiceAttributeInterface;

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