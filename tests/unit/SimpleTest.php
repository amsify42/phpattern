<?php

namespace Amsify42\Tests;

use PHPUnit\Framework\TestCase;

final class SimpleTest extends TestCase
{
    public function testSimple()
    {
        /**
         * Initiating Application
         */
        $init = new \Tests\Init();
        /**
         * Acquiring request and rendering the response
         */
        render_response($init->acquireRequest());
    }

}