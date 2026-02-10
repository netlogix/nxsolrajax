<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet\Facets\OptionBased\Options;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Options\OptionsFacet;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class OptionsFacetTest extends UnitTestCase
{
    #[Test]
    public function itCanBeSerializedToJSON(): void
    {
        $resultSet = new SearchResultSet();

        $name = uniqid('name_');
        $field = uniqid('field_');
        $label = uniqid('label_');
        $configuration = ['foo' => uniqid('bar_')];
        $resetUrl = sprintf('https://www.example.com/%s', $name);
        $isUsed = random_int(0, 1) === 1;

        $subject = $this->getMockBuilder(OptionsFacet::class)
            ->setConstructorArgs([$resultSet, $name, $field, $label, $configuration])
            ->onlyMethods(['getFacetResetUrl'])
            ->getMock();

        $subject->method('getFacetResetUrl')->willReturn($resetUrl);

        $subject->setIsUsed($isUsed);

        $jsonString = json_encode($subject);
        $this->assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        $this->assertIsArray($jsonData);

        $this->assertArrayHasKey('name', $jsonData);
        $this->assertEquals($name, $jsonData['name']);

        $this->assertArrayHasKey('type', $jsonData);
        $this->assertEquals(
            \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Options\OptionsFacet::TYPE_OPTIONS,
            $jsonData['type'],
        );

        $this->assertArrayHasKey('label', $jsonData);
        $this->assertEquals($label, $jsonData['label']);

        $this->assertArrayHasKey('used', $jsonData);
        $this->assertEquals($isUsed, $jsonData['used']);

        $this->assertArrayHasKey('options', $jsonData);
        $this->assertEquals([], $jsonData['options']);

        $this->assertArrayHasKey('links', $jsonData);
        $this->assertArrayHasKey('reset', $jsonData['links']);
        $this->assertEquals($resetUrl, $jsonData['links']['reset']);
    }
}
