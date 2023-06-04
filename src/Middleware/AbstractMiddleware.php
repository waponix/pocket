<?php
namespace Waponix\Pocket\Middleware;

abstract class AbstractMiddleware implements MiddlewareInterface
{
    public function next(): true
    {
        return true;
    }

    public function skip(int $skip = 1): int
    {
        return $skip;
    }
}