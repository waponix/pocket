<?php

use PHPUnit\Framework\TestCase;
use Waponix\Pocket\Exception\ClassNotFoundException;
use Waponix\Pocket\Iterator\ClassCollector;

class ClassCollectorTest extends TestCase
{
    public function testShouldBeInstanceOfIterator()
    {
        $classCollector = new ClassCollector(ClassCollector::class);
        
        $this->assertInstanceOf(\Iterator::class, $classCollector);
    }

    public function testShouldThrowClassNotFoundException()
    {
        $this->expectException(ClassNotFoundException::class);
        
        $classCollector = new ClassCollector('NonExistingClass');
    }
}