<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\HierarchyFacet;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\Node;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

final class NodeTest extends UnitTestCase
{
    #[Test]
    public function itCanDetermineOwnActiveStateIfSelected(): void
    {
        $facetMock = $this->createStub(HierarchyFacet::class);

        $subject = new Node($facetMock, null, uniqid('key_'), '', uniqid('value_'), 0, true);

        $this->assertTrue($subject->isActive());
    }

    #[Test]
    public function itCanDetermineOwnActiveStateIfNotSelected(): void
    {
        $facetMock = $this->createStub(HierarchyFacet::class);

        $subject = new Node($facetMock, null, uniqid('key_'), '', uniqid('value_'), 0, false);

        $this->assertFalse($subject->isActive());
    }

    #[Test]
    public function itCanDetermineOwnActiveStateIfOneChildIsActive(): void
    {
        $facetMock = $this->createStub(HierarchyFacet::class);

        $subject = new Node($facetMock, null, uniqid('key_'), '', uniqid('value_'), 0, false);

        $subject->addChildNode(new Node($facetMock, $subject, uniqid('key_'), '', uniqid('value_'), 0, false));
        $subject->addChildNode(new Node($facetMock, $subject, uniqid('key_'), '', uniqid('value_'), 0, true));
        $subject->addChildNode(new Node($facetMock, $subject, uniqid('key_'), '', uniqid('value_'), 0, false));

        $this->assertTrue($subject->isActive());
    }

    #[Test]
    public function itCanBeSerializedToJSON(): void
    {
        $facetMock = $this->createStub(HierarchyFacet::class);

        $key = uniqid('key_');
        $value = uniqid('value_');
        $label = uniqid('lebel_');
        $count = random_int(0, 999);
        $selected = $count % 2 === 0;
        $url = sprintf('https://www.example.com/%s', $key);

        $subject = $this->getMockBuilder(Node::class)
            ->setConstructorArgs([$facetMock, null, $key, $label, $value, $count, $selected])
            ->onlyMethods(['getFacetItemUrl'])
            ->getMock();

        $subject->method('getFacetItemUrl')->willReturn($url);

        $jsonString = json_encode($subject);
        $this->assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        $this->assertIsArray($jsonData);

        $this->assertArrayHasKey('id', $jsonData);
        $this->assertEquals($key, $jsonData['id']);

        $this->assertArrayHasKey('label', $jsonData);
        $this->assertEquals($label, $jsonData['label']);

        $this->assertArrayHasKey('name', $jsonData);
        $this->assertEquals($value, $jsonData['name']);

        $this->assertArrayHasKey('count', $jsonData);
        $this->assertEquals($count, $jsonData['count']);

        $this->assertArrayHasKey('selected', $jsonData);
        $this->assertEquals($selected, $jsonData['selected']);

        $this->assertArrayHasKey('active', $jsonData);
        $this->assertEquals($selected, $jsonData['active']);

        $this->assertArrayHasKey('options', $jsonData);
        // no child data is set
        $this->assertEmpty($jsonData['options']);

        $this->assertArrayHasKey('links', $jsonData);
        $this->assertArrayHasKey('self', $jsonData['links']);
        $this->assertEquals($url, $jsonData['links']['self']);
    }
}
