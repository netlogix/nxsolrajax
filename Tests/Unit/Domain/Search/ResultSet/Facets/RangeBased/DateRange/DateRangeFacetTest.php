<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet\Facets\RangeBased\DateRange;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRangeFacet as DateRangeFacetAlias;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRangeFacet;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class DateRangeFacetTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function itCanBeSerializedToJSON()
    {
        $searchResultSet = new SearchResultSet();

        $name = uniqid('name_');
        $field = uniqid('_field');
        $label = uniqid('_label');
        $isUsed = rand(0, 999) % 2 == 0;
        $resetUrl = sprintf('https://www.example.com/%s', $name);
        $options = ['foo' => uniqid('bar_')];

        $subject = $this->getMockBuilder(DateRangeFacet::class)
            ->setConstructorArgs(
                [
                    $searchResultSet,
                    $name,
                    $field,
                    $label,
                    $options,
                    $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock()
                ]
            )
            ->onlyMethods(['getResetUrl'])
            ->getMock();

        $subject->method('getResetUrl')->willReturn($resetUrl);
        $subject->setIsUsed($isUsed);

        $jsonString = json_encode($subject);

        self::assertNotEmpty($jsonString);

        $jsonData = json_decode($jsonString, true);
        self::assertIsArray($jsonData);
        self::assertNotEmpty($jsonData);

        self::assertArrayHasKey('name', $jsonData);
        self::assertEquals($jsonData['name'], $name);

        self::assertArrayHasKey('type', $jsonData);
        self::assertEquals(DateRangeFacetAlias::TYPE_DATE_RANGE, $jsonData['type']);

        self::assertArrayHasKey('label', $jsonData);
        self::assertEquals($jsonData['label'], $label);

        self::assertArrayHasKey('used', $jsonData);
        self::assertEquals($jsonData['used'], $isUsed);

        self::assertArrayHasKey('options', $jsonData);
        // fixme this does not export options but dateRange
//        self::assertEquals($jsonData['options'], $options);

        self::assertArrayHasKey('links', $jsonData);
        self::assertArrayHasKey('reset', $jsonData['links']);
        self::assertEquals($jsonData['links']['reset'], $resetUrl);
    }
}
