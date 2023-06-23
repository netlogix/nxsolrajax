<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet\Facets\OptionBased\Options;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Options\OptionsFacet;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class OptionsFacetTest extends UnitTestCase
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
        $isUsed = rand(0, 1) == 1;

        $subject = $this->getMockBuilder(OptionsFacet::class)
            ->setConstructorArgs([
                $resultSet,
                $name,
                $field,
                $label,
                $configuration
            ])
            ->onlyMethods(['getFacetResetUrl'])
            ->getMock();

        $subject->method('getFacetResetUrl')->willReturn($resetUrl);

        $subject->setIsUsed($isUsed);

        $jsonString = json_encode($subject);
        self::assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        self::assertIsArray($jsonData);

        self::assertArrayHasKey('name', $jsonData);
        self::assertEquals($name, $jsonData['name']);

        self::assertArrayHasKey('type', $jsonData);
        self::assertEquals(
            \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Options\OptionsFacet::TYPE_OPTIONS,
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
