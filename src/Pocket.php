<?php
namespace Pocket;

class Pocket
{
    private array $classPouch = [];
    private readonly Pouch $pouch;

    public function __construct()
    {
        $this->pouch = new Pouch('./cache');
    }
    
    public function get()
    {

    }
}