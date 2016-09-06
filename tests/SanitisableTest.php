<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/resources/Container.php';

class SanitisableTest extends PHPUnit_Framework_TestCase {

    /**
     * @test
     */
    function it_instantiates() {
        $c = new Container;
        $this->assertInstanceOf(Container::class,$c);
    }

    /**
     * to be continued...
     */
}