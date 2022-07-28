<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet\Grouping;

use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequest;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping\Group;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping\GroupItem;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class GroupItemTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function itCanBeSerializedToJSON()
    {
        $groupName = uniqid('groupName_');
        $resultsPerPage = rand(1, 99999);

        $groupValue = uniqid('groupValue_');
        $numFound = rand(0, 999);
        $start = rand(0, 99);
        $maxScore = rand(1, 99999) * .1;

        $group = new Group($groupName, $resultsPerPage);

        $usedSearchRequestMock = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject = new GroupItem($group, $groupValue, $numFound, $start, $maxScore, $usedSearchRequestMock);

        $groupUrl = sprintf('https://www.example.de/%s', $groupName);
        $subject->setGroupUrl($groupUrl);

        $groupLabel = uniqid('groupLabel_');
        $subject->setGroupLabel($groupLabel);

        $jsonString = json_encode($subject);
        self::assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        self::assertIsArray($jsonData);

        self::assertArrayHasKey('label', $jsonData);
        self::assertEquals($groupLabel, $jsonData['label']);

        self::assertArrayHasKey('name', $jsonData);
        self::assertEquals($groupValue, $jsonData['name']);

        self::assertArrayHasKey('totalResults', $jsonData);
        self::assertEquals($numFound, $jsonData['totalResults']);

        self::assertArrayHasKey('start', $jsonData);
        self::assertEquals($start, $jsonData['start']);

        self::assertArrayHasKey('maxScore', $jsonData);
        self::assertEquals($maxScore, $jsonData['maxScore']);

        self::assertArrayHasKey('url', $jsonData);
        self::assertEquals($groupUrl, $jsonData['url']);

        self::assertArrayHasKey('items', $jsonData);
        self::assertEquals([], $jsonData['items']);
    }
}
