<?php
namespace Waponix\Pocket\Attribute\Interface;

use Waponix\Pocket\Attribute\Factory;

interface ServiceAttributeInterface
{
    public function getArgs(): array;
    public function getFactory(): ?Factory;
    public function getTags(): null|string|array;
} 