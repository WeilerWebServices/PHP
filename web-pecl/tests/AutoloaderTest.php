<?php

/*
  +----------------------------------------------------------------------+
  | The PECL website                                                     |
  +----------------------------------------------------------------------+
  | Copyright (c) 1999-2019 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | https://php.net/license/3_01.txt                                     |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Authors: Peter Kokot <petk@php.net>                                  |
  +----------------------------------------------------------------------+
*/

namespace App\Tests;

use App\Autoloader;
use PHPUnit\Framework\TestCase;

class MockAutoloader extends Autoloader
{
    protected $files = [];

    public function setFiles(array $files)
    {
        $this->files = $files;
    }

    protected function requireFile($file)
    {
        return in_array($file, $this->files);
    }
}

class AutoloaderTest extends TestCase
{
    protected $autoloader;

    protected function setUp()
    {
        $this->autoloader = new MockAutoloader;

        $this->autoloader->setFiles([
            '/vendor/foo.bar/src/ClassName.php',
            '/vendor/foo.bar/src/DoomClassName.php',
            '/vendor/foo.bar/tests/ClassNameTest.php',
            '/vendor/foo.bardoom/src/ClassName.php',
            '/vendor/foo.bar.baz.dib/src/ClassName.php',
            '/vendor/foo.bar.baz.dib.zim.gir/src/ClassName.php',
            '/src/lib/ClassName.php',
            '/src/libfoo/ClassFoo.php',
        ]);

        $this->autoloader->addNamespace(
            'Foo\Bar',
            '/vendor/foo.bar/src'
        );

        $this->autoloader->addNamespace(
            'Foo\Bar',
            '/vendor/foo.bar/tests'
        );

        $this->autoloader->addNamespace(
            'Foo\\BarDoom',
            '/vendor/foo.bardoom/src/'
        );

        $this->autoloader->addNamespace(
            'Foo\Bar\Baz\Dib',
            '/vendor/foo.bar.baz.dib/src/'
        );

        $this->autoloader->addNamespace(
            'Foo\Bar\Baz\Dib\Zim\Gir',
            '/vendor/foo.bar.baz.dib.zim.gir/src/'
        );

        $this->autoloader->addClassmap(
            'ClassName',
            '/src/lib/ClassName.php'
        );

        $this->autoloader->addClassmap(
            'ClassFoo',
            '/src/libfoo/ClassFoo.php'
        );
    }

    /**
     * @dataProvider classesProvider
     */
    public function testLoad($class, $expected)
    {
        $this->assertEquals($expected, $this->autoloader->load($class));
    }

    public function classesProvider()
    {
        return [
            ['Foo\Bar\ClassName', '/vendor/foo.bar/src/ClassName.php'],
            ['Foo\Bar\ClassNameTest', '/vendor/foo.bar/tests/ClassNameTest.php'],
            ['ClassName', '/src/lib/ClassName.php'],
            ['ClassFoo', '/src/libfoo/ClassFoo.php'],
            ['No_Vendor\No_Package\NoClass', false],
            ['Foo\Bar\Baz\Dib\Zim\Gir\ClassName', '/vendor/foo.bar.baz.dib.zim.gir/src/ClassName.php'],
            ['Foo\Bar\DoomClassName', '/vendor/foo.bar/src/DoomClassName.php'],
            ['Foo\BarDoom\ClassName', '/vendor/foo.bardoom/src/ClassName.php'],
        ];
    }
}
