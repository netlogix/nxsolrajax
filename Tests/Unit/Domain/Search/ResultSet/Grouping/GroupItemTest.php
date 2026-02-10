<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet\Grouping;

use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequest;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping\Group;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping\GroupItem;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class GroupItemTest extends UnitTestCase
{
    #[Test]
    public function itCanBeSerializedToJSON(): void
    {
        $groupName = uniqid('groupName_');
        $resultsPerPage = random_int(1, 99999);

        $groupValue = uniqid('groupValue_');
        $numFound = random_int(0, 999);
        $start = random_int(0, 99);
        $maxScore = random_int(1, 99999) * 0.1;

        $group = new Group($groupName, $resultsPerPage);

        $usedSearchRequestMock = $this->createStub(SearchRequest::class);

        $subject = new GroupItem($group, $groupValue, $numFound, $start, $maxScore, $usedSearchRequestMock);

        $groupUrl = sprintf('https://www.example.de/%s', $groupName);
        $subject->setGroupUrl($groupUrl);

        $groupLabel = uniqid('groupLabel_');
        $subject->setGroupLabel($groupLabel);

        $jsonString = json_encode($subject);
        $this->assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        $this->assertIsArray($jsonData);

        $this->assertArrayHasKey('label', $jsonData);
        $this->assertEquals($groupLabel, $jsonData['label']);

        $this->assertArrayHasKey('name', $jsonData);
        $this->assertEquals($groupValue, $jsonData['name']);

        $this->assertArrayHasKey('totalResults', $jsonData);
        $this->assertEquals($numFound, $jsonData['totalResults']);

        $this->assertArrayHasKey('start', $jsonData);
        $this->assertEquals($start, $jsonData['start']);

        $this->assertArrayHasKey('maxScore', $jsonData);
        $this->assertEquals($maxScore, $jsonData['maxScore']);

        $this->assertArrayHasKey('url', $jsonData);
        $this->assertEquals($groupUrl, $jsonData['url']);

        $this->assertArrayHasKey('items', $jsonData);
        $this->assertEquals([], $jsonData['items']);
    }
}
