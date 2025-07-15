<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet\Grouping;

use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping\Group;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class GroupTest extends UnitTestCase
{

    #[Test]
    public function itCanBeSerializedToJSON(): void
    {
        $groupName = uniqid('groupName_');
        $resultsPerPage = random_int(1, 99999);

        $subject = new Group($groupName, $resultsPerPage);

        $jsonString = json_encode($subject);
        $this->assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        $this->assertIsArray($jsonData);

        $this->assertArrayHasKey('groupName', $jsonData);
        $this->assertEquals($groupName, $jsonData['groupName']);

        $this->assertArrayHasKey('resultsPerPage', $jsonData);
        $this->assertEquals($resultsPerPage, $jsonData['resultsPerPage']);

        $this->assertArrayHasKey('groupItems', $jsonData);
        $this->assertEquals([], $jsonData['groupItems']);
    }
}
