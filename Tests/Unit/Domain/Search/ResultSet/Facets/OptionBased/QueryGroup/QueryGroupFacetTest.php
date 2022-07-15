<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\QueryGroupFacet;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class QueryGroupFacetTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function itCanBesSerializedToJSON()
    {
        $name = uniqid('name_');
        $field = uniqid('field_');
        $label = uniqid('label_');
        $resetUrl = sprintf('https://www.example.com/%s', $name);
        $isUsed = rand(0, 1) == 0;

        $subject = $this->getMockBuilder(QueryGroupFacet::class)
            ->setConstructorArgs([
                new SearchResultSet(),
                $name,
                $field,
                $label,
                []
            ])
            ->onlyMethods(['getResetUrl'])
            ->getMock();

        $subject->method('getResetUrl')->willReturn($resetUrl);
        $subject->setIsUsed($isUsed);

        $jsonString = json_encode($subject);
        self::assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        self::assertIsArray($jsonData);

        self::assertArrayHasKey('name', $jsonData);
        self::assertEquals($name, $jsonData['name']);

        self::assertArrayHasKey('type', $jsonData);
        self::assertEquals(
            \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\QueryGroupFacet::TYPE_QUERY_GROUP,
            $jsonData['type']
        );

        self::assertArrayHasKey('label', $jsonData);
        self::assertEquals($label, $jsonData['label']);

        self::assertArrayHasKey('used', $jsonData);
        self::assertEquals($isUsed, $jsonData['used']);

        self::assertArrayHasKey('options', $jsonData);
        self::assertEquals([], $jsonData['options']);

        self::assertArrayHasKey('links', $jsonData);
        self::assertArrayHasKey('reset', $jsonData['links']);
        self::assertEquals($resetUrl, $jsonData['links']['reset']);
    }
}