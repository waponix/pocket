<?php   
namespace Waponix\Pocket\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class MiddlewareAttribute
{
    public function __construct(
        private readonly array $middlewares = []
    )
    {}

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}