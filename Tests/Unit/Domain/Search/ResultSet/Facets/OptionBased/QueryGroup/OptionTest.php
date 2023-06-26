<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\QueryGroupFacet;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\Option;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class OptionTest extends UnitTestCase
{

    #[Test]
    public function itCanBesSerializedToJSON(): void
    {
        $queryGroupFacet = new QueryGroupFacet(
            new SearchResultSet(),
            uniqid('name_'),
            uniqid('field_'),
            '',
            []
        );


        $label = uniqid('label_');
        $value = uniqid('value_');
        $documentCount = rand(0, 999);
        $selected = $documentCount % 2 == 0;
        $url = sprintf('https://www.example.com/%s', $value);

        $subject = $this->getMockBuilder(Option::class)
            ->setConstructorArgs([
                $queryGroupFacet,
                $label,
                $value,
                $documentCount,
                $selected
            ])
            ->onlyMethods(['getFacetItemUrl'])
            ->getMock();

        $subject->method('getFacetItemUrl')->willReturn($url);

        $jsonString = json_encode($subject);
        self::assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        self::assertIsArray($jsonData);

        self::assertArrayHasKey('label', $jsonData);
        self::assertEquals($label, $jsonData['label']);

        self::assertArrayHasKey('name', $jsonData);
        self::assertEquals($value, $jsonData['name']);

        self::assertArrayHasKey('count', $jsonData);
        self::assertEquals($documentCount, $jsonData['count']);

        self::assertArrayHasKey('selected', $jsonData);
        self::assertEquals($selected, $jsonData['selected']);

        self::assertArrayHasKey('links', $jsonData);
        self::assertArrayHasKey('self', $jsonData['links']);
        self::assertEquals($url, $jsonData['links']['self']);
    }
}
