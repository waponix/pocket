<?php

use PHPUnit\Framework\TestCase;
use Pocket\ClassCollector;
use Pocket\Exception\ClassNotFoundException;

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