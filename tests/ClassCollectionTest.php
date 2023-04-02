<?php

use PHPUnit\Framework\TestCase;
use Waponix\Pocket\Exception\ClassNotFoundException;
use Waponix\Pocket\Iterator\ClassCollection;

class ClassCollectionTest extends TestCase
{
    public function testShouldBeInstanceOfIterator()
    {
        $classCollection = new ClassCollection(ClassCollection::class);
        
        $this->assertInstanceOf(\Iterator::class, $classCollection);
    }

    public function testShouldThrowClassNotFoundException()
    {
        $this->expectException(ClassNotFoundException::class);
        
        $classCollection = new ClassCollection('NonExistingClass');
    }
}