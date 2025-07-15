<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet\Facets\OptionBased\Options;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacet;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Options\OptionsFacet;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequest;
use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Options\Option;
use Netlogix\Nxsolrajax\Event\Url\GenerateFacetItemUrlEvent;
use Netlogix\Nxsolrajax\Event\Url\GenerateFacetResetUrlEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class OptionTest extends UnitTestCase
{
    #[Test]
    public function itCanBeSerializedToJSON(): void
    {
        $name = uniqid('name_');
        $field = uniqid('field_');
        $label = uniqid('label_');
        $value = uniqid('value_');
        $documentCount = random_int(0, 9999);
        $isSelected = $documentCount % 2 == 0;
        $configuration = ['foo' => uniqid('bar_')];

        $facet = new OptionsFacet(
            new SearchResultSet(),
            $name,
            $field,
            $label,
            $configuration
        );

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
        $this->assertEquals($isSelected, $jsonData['selected']);

        $this->assertArrayHasKey('links', $jsonData);
        $this->assertArrayHasKey('self', $jsonData['links']);
        $this->assertEquals($url, $jsonData['links']['self']);
        $this->assertArrayHasKey('reset', $jsonData['links']);
        $this->assertEquals($resetUrl, $jsonData['links']['reset']);
    }

    #[Test]
    public function dispatchGenerateFacetItemUrlEventForUrlGeneration(): void
    {
        $facet = $this->getFacet();
        $option = new Option(
            facet: $facet,
            label: 'foo',
            value: 'bar',
            documentCount: 10
        );

        $this->registerEventUrlEvent(GenerateFacetItemUrlEvent::class, $option, 'https://www.example.com/');
        $this->assertSame('https://www.example.com/', $option->getUrl());
    }

    #[Test]
    public function generateUrl(): void
    {
        $facet = $this->getFacet();
        $searchUriBuilder = $this->getSearchUriBuilder();

        $option = new Option(
            facet: $facet,
            label: 'foo',
            value: 'bar',
            documentCount: 10
        );


        $this->registerEventUrlEvent(GenerateFacetItemUrlEvent::class, $option, '');

        $facet->expects($this->once())->method('getConfiguration')->willReturn([
            'keepAllOptionsOnSelection' => 1,
            'operator' => 'or',
        ]);

        $searchUriBuilder->expects($this->once())->method('getAddFacetValueUri')->with(
            self::isInstanceOf(SearchRequest::class),
            'foo_bar',
            'bar'
        )->willReturn('https://www.example.com/');
        $option->setSearchUriBuilder($searchUriBuilder);

        $this->assertSame('https://www.example.com/', $option->getUrl());
    }

    #[Test]
    public function dispatchGenerateFacetResetUrlEventForResetUrlGeneration(): void
    {
        $facet = $this->getFacet();
        $option = new Option(
            facet: $facet,
            label: 'foo',
            value: 'bar',
            documentCount: 10
        );

        $this->registerEventUrlEvent(GenerateFacetResetUrlEvent::class, $option->getFacet(), 'https://www.example.com/');
        $this->assertSame('https://www.example.com/', $option->getResetUrl());
    }

    #[Test]
    public function generateResetUrl(): void
    {
        $facet = $this->getFacet();
        $searchUriBuilder = $this->getSearchUriBuilder();

        $option = new Option(
            facet: $facet,
            label: 'foo',
            value: 'bar',
            documentCount: 10
        );


        $this->registerEventUrlEvent(GenerateFacetResetUrlEvent::class, $option->getFacet(), '');

        $facet->expects($this->once())->method('getConfiguration')->willReturn([
            'keepAllOptionsOnSelection' => 1,
            'operator' => 'or',
        ]);

        $searchUriBuilder->expects($this->once())->method('getRemoveFacetValueUri')->with(
            self::isInstanceOf(SearchRequest::class),
            'foo_bar',
            'bar'
        )->willReturn('https://www.example.com/');
        $option->setSearchUriBuilder($searchUriBuilder);

        $this->assertSame('https://www.example.com/', $option->getResetUrl());
    }

    private function getFacet(): OptionsFacet&MockObject
    {
        $facet = $this->getMockBuilder(OptionsFacet::class)
            ->disableOriginalConstructor()
            ->getMock();
        $facet->method('getName')->willReturn('foo_bar');
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);

        $facet->method('getResultSet')->willReturn($searchResultSet);

        return $facet;
    }

    private function getSearchUriBuilder(): SearchUriBuilder&MockObject
    {
        return $this->getMockBuilder(SearchUriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function registerEventUrlEvent(string $className, Option|AbstractFacet|string ...$args): EventDispatcherInterface&MockObject
    {
        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $eventDispatcher->expects($this->once())->method('dispatch')->with(
            self::isInstanceOf($className)
        )->willReturn(new $className(...$args));

        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);

        return $eventDispatcher;
    }
}
