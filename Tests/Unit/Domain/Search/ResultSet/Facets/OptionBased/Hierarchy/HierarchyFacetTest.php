<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\HierarchyFacet;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class HierarchyFacetTest extends UnitTestCase
{

    #[Test]
    public function itCanBeSerializedToJSON(): void
    {
        $resultSet = new SearchResultSet();

        $name = uniqid('name_');
        $field = uniqid('field_');
        $label = uniqid('label_');
        $configuration = ['foo' => uniqid('bar_')];
        $url = sprintf('https://www.example.com/%s', $name);
        $isUsed = rand(1, 2) == 1;

        $subject = $this->getMockBuilder(HierarchyFacet::class)
            ->setConstructorArgs([
                $resultSet,
                $name,
                $field,
                $label,
                $configuration
            ])
            ->onlyMethods(['getFacetResetUrl'])
            ->getMock();
        $subject->method('getFacetResetUrl')->willReturn($url);
        $subject->setIsUsed($isUsed);


        $jsonString = json_encode($subject);
        self::assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        self::assertIsArray($jsonData);

        self::assertArrayHasKey('name', $jsonData);
        self::assertEquals($name, $jsonData['name']);

        self::assertArrayHasKey('type', $jsonData);
        self::assertEquals(
            \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\HierarchyFacet::TYPE_HIERARCHY,
            $jsonData['type']
        );

        self::assertArrayHasKey('label', $jsonData);
        self::assertEquals($label, $jsonData['label']);

        self::assertArrayHasKey('used', $jsonData);
        self::assertEquals($isUsed, $jsonData['used']);

        // no child nodes have been set
        self::assertArrayHasKey('options', $jsonData);
        self::assertEquals([], $jsonData['options']);

        self::assertArrayHasKey('links', $jsonData);
        self::assertArrayHasKey('reset', $jsonData['links']);
        self::assertEquals($url, $jsonData['links']['reset']);
    }
}
