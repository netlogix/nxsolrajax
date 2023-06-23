<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet\Facets\OptionBased\Options;

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
        $documentCount = rand(0, 9999);
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

    #[Test]
    public function dispatchGenerateFacetItemUrlEventForUrlGeneration()
    {
        $facet = $this->getFacet();
        $option = new Option(
            facet: $facet,
            label: 'foo',
            value: 'bar',
            documentCount: 10
        );

        $this->registerEventUrlEvent(GenerateFacetItemUrlEvent::class, $option, 'https://www.example.com/');
        self::assertEquals('https://www.example.com/', $option->getUrl());
    }

    #[Test]
    public function generateUrl()
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

        $facet->expects(self::once())->method('getConfiguration')->willReturn([
            'keepAllOptionsOnSelection' => 1,
            'operator' => 'or',
        ]);

        $searchUriBuilder->expects(self::once())->method('getAddFacetValueUri')->with(
            self::isInstanceOf(SearchRequest::class),
            'foo_bar',
            'bar'
        )->willReturn('https://www.example.com/');

        self::assertEquals('https://www.example.com/', $option->getUrl());
    }

    #[Test]
    public function dispatchGenerateFacetResetUrlEventForResetUrlGeneration()
    {
        $facet = $this->getFacet();
        $option = new Option(
            facet: $facet,
            label: 'foo',
            value: 'bar',
            documentCount: 10
        );

        $this->registerEventUrlEvent(GenerateFacetResetUrlEvent::class, $option->getFacet(), 'https://www.example.com/');
        self::assertEquals('https://www.example.com/', $option->getResetUrl());
    }

    #[Test]
    public function generateResetUrl()
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

        $facet->expects(self::once())->method('getConfiguration')->willReturn([
            'keepAllOptionsOnSelection' => 1,
            'operator' => 'or',
        ]);

        $searchUriBuilder->expects(self::once())->method('getRemoveFacetValueUri')->with(
            self::isInstanceOf(SearchRequest::class),
            'foo_bar',
            'bar'
        )->willReturn('https://www.example.com/');

        self::assertEquals('https://www.example.com/', $option->getResetUrl());
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
        $searchUriBuilder = $this->getMockBuilder(SearchUriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        GeneralUtility::addInstance(SearchUriBuilder::class, $searchUriBuilder);

        return $searchUriBuilder;
    }

    private function registerEventUrlEvent(string $className, ...$args): EventDispatcherInterface&MockObject
    {
        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $eventDispatcher->expects(self::once())->method('dispatch')->with(
            self::isInstanceOf($className)
        )->willReturn(new $className(...$args));

        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);

        return $eventDispatcher;
    }
}
