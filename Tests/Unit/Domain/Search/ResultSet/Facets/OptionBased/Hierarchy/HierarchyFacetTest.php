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
        $isUsed = random_int(1, 2) == 1;

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
        $this->assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        $this->assertIsArray($jsonData);

        $this->assertArrayHasKey('name', $jsonData);
        $this->assertEquals($name, $jsonData['name']);

        $this->assertArrayHasKey('type', $jsonData);
        $this->assertEquals(\ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\HierarchyFacet::TYPE_HIERARCHY, $jsonData['type']);

        $this->assertArrayHasKey('label', $jsonData);
        $this->assertEquals($label, $jsonData['label']);

        $this->assertArrayHasKey('used', $jsonData);
        $this->assertEquals($isUsed, $jsonData['used']);

        // no child nodes have been set
        $this->assertArrayHasKey('options', $jsonData);
        $this->assertEquals([], $jsonData['options']);

        $this->assertArrayHasKey('links', $jsonData);
        $this->assertArrayHasKey('reset', $jsonData['links']);
        $this->assertEquals($url, $jsonData['links']['reset']);
    }
}
