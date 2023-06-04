<?php
namespace Waponix\Pocket\Middleware;

interface MiddlewareInterface
{
    public function next(): true;
    public function skip(): int;
}