<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet\Facets\RangeBased\DateRange;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRangeFacet as DateRangeFacetAlias;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRangeFacet;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DateRangeFacetTest extends UnitTestCase
{
    #[Test]
    public function itCanBeSerializedToJSON(): void
    {
        $searchResultSet = new SearchResultSet();

        $name = uniqid('name_');
        $field = uniqid('_field');
        $label = uniqid('_label');
        $isUsed = random_int(0, 999) % 2 === 0;
        $resetUrl = sprintf('https://www.example.com/%s', $name);
        $options = ['foo' => uniqid('bar_')];

        $subject = $this->getMockBuilder(DateRangeFacet::class)
            ->setConstructorArgs([$searchResultSet, $name, $field, $label, $options])
            ->onlyMethods(['getFacetResetUrl'])
            ->getMock();

        $subject->method('getFacetResetUrl')->willReturn($resetUrl);
        $subject->setIsUsed($isUsed);

        $jsonString = json_encode($subject);

        $this->assertNotEmpty($jsonString);

        $jsonData = json_decode($jsonString, true);
        $this->assertIsArray($jsonData);
        $this->assertNotEmpty($jsonData);

        $this->assertArrayHasKey('name', $jsonData);
        $this->assertEquals($jsonData['name'], $name);

        $this->assertArrayHasKey('type', $jsonData);
        $this->assertEquals(DateRangeFacetAlias::TYPE_DATE_RANGE, $jsonData['type']);

        $this->assertArrayHasKey('label', $jsonData);
        $this->assertEquals($jsonData['label'], $label);

        $this->assertArrayHasKey('used', $jsonData);
        $this->assertEquals($jsonData['used'], $isUsed);

        $this->assertArrayHasKey('options', $jsonData);
        // fixme this does not export options but dateRange
        //        self::assertEquals($jsonData['options'], $options);

        $this->assertArrayHasKey('links', $jsonData);
        $this->assertArrayHasKey('reset', $jsonData['links']);
        $this->assertEquals($jsonData['links']['reset'], $resetUrl);
    }
}
