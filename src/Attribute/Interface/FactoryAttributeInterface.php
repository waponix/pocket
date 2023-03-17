<?php
namespace Waponix\Pocket\Attribute\Interface;

interface FactoryAttributeInterface
{
    public function getClass(): string;
    public function getMethod(): string;
    public function getArgs(): array;
}