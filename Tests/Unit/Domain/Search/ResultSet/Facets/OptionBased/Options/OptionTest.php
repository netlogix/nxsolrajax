<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet\Facets\OptionBased\Options;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Options\OptionsFacet;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Options\Option;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class OptionTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function itCanBeSerializedToJSON()
    {
        $name = uniqid('name_');
        $field = uniqid('field_');
        $label = uniqid('label_');
        $value = uniqid('value_');
        $documentCount = rand(0, 9999);
        $isSelected = $documentCount % 2 == 0;
        $configuration = ['foo' => uniqid('bar_')];

        $facet = new OptionsFacet(new SearchResultSet(), $name, $field, $label, $configuration);


        $subject = $this->getMockBuilder(Option::class)
            ->setConstructorArgs([
                $facet,
                $label,
                $value,
                $documentCount,
                $isSelected,
                []
            ])
            ->onlyMethods(['getUrl', 'getResetUrl'])
            ->getMock();

        $url = sprintf('https://www.example.com/%s', uniqid('url_'));
        $resetUrl = sprintf('https://www.example.com/%s', uniqid('resetUrl_'));

        $subject->method('getUrl')->willReturn($url);
        $subject->method('getResetUrl')->willReturn($resetUrl);

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
        self::assertEquals($isSelected, $jsonData['selected']);

        self::assertArrayHasKey('links', $jsonData);
        self::assertArrayHasKey('self', $jsonData['links']);
        self::assertEquals($url, $jsonData['links']['self']);
        self::assertArrayHasKey('reset', $jsonData['links']);
        self::assertEquals($resetUrl, $jsonData['links']['reset']);
    }
}