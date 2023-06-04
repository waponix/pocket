<?php
namespace Waponix\Pocket\Attribute\Interface;

use Waponix\Pocket\Attribute\FactoryAttribute;

interface ServiceAttributeInterface
{
    public function getArgs(): array;
    public function getFactory(): ?FactoryAttribute;
    public function getTags(): null|string|array;
} 