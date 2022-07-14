<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Functional;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;

class DummyTest extends FunctionalTestCase {

    /**
     * @test
     * @return void
     */
    public function itIsAlive() {
        self::assertTrue(true);
    }
}
