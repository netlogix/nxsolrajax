<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\QueryGroupFacet;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\Option;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class OptionTest extends UnitTestCase
{
    #[Test]
    public function itCanBesSerializedToJSON(): void
    {
        $queryGroupFacet = new QueryGroupFacet(new SearchResultSet(), uniqid('name_'), uniqid('field_'), '', []);

        $label = uniqid('label_');
        $value = uniqid('value_');
        $documentCount = random_int(0, 999);
        $selected = $documentCount % 2 === 0;
        $url = sprintf('https://www.example.com/%s', $value);

        $subject = $this->getMockBuilder(Option::class)
            ->setConstructorArgs([$queryGroupFacet, $label, $value, $documentCount, $selected])
            ->onlyMethods(['getFacetItemUrl'])
            ->getMock();

        $subject->method('getFacetItemUrl')->willReturn($url);

        $jsonString = json_encode($subject);
        $this->assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        $this->assertIsArray($jsonData);

        $this->assertArrayHasKey('label', $jsonData);
        $this->assertEquals($label, $jsonData['label']);

        $this->assertArrayHasKey('name', $jsonData);
        $this->assertEquals($value, $jsonData['name']);

        $this->assertArrayHasKey('count', $jsonData);
        $this->assertEquals($documentCount, $jsonData['count']);

        $this->assertArrayHasKey('selected', $jsonData);
        $this->assertEquals($selected, $jsonData['selected']);

        $this->assertArrayHasKey('links', $jsonData);
        $this->assertArrayHasKey('self', $jsonData['links']);
        $this->assertEquals($url, $jsonData['links']['self']);
    }
}
