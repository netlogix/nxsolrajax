<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet\Grouping;

use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping\Group;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class GroupTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function itCanBeSerializedToJSON()
    {
        $groupName = uniqid('groupName_');
        $resultsPerPage = rand(1, 99999);

        $subject = new Group($groupName, $resultsPerPage);

        $jsonString = json_encode($subject);
        self::assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        self::assertIsArray($jsonData);

        self::assertArrayHasKey('groupName', $jsonData);
        self::assertEquals($groupName, $jsonData['groupName']);

        self::assertArrayHasKey('resultsPerPage', $jsonData);
        self::assertEquals($resultsPerPage, $jsonData['resultsPerPage']);

        self::assertArrayHasKey('groupItems', $jsonData);
        self::assertEquals([], $jsonData['groupItems']);
    }
}
