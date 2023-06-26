<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Traits;


use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacet;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacetItem;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Options\OptionsFacet;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequest;
use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use Netlogix\Nxsolrajax\Event\Url\GenerateFacetItemUrlEvent;
use Netlogix\Nxsolrajax\Event\Url\GenerateFacetResetUrlEvent;
use Netlogix\Nxsolrajax\Traits\FacetUrlTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FacetUrlTraitTestClass
{
    use FacetUrlTrait;

    public function getUrl(AbstractFacetItem $facetItem, string $overrideUriValue = ''): string
    {
        return $this->getFacetItemUrl($facetItem, $overrideUriValue);
    }

    public function getResetUrl(AbstractFacet $facet): string
    {
        return $this->getFacetResetUrl($facet);
    }
}

class FacetUrlTraitTest extends UnitTestCase
{

    #[Test]
    public function dispatchGenerateFacetItemUrlEventForUrlGeneration()
    {
        $trait = new FacetUrlTraitTestClass();

        $option = $this->getMockBuilder(AbstractFacetItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registerEventUrlEvent(GenerateFacetItemUrlEvent::class, $option, 'https://www.example.com/');
        self::assertEquals('https://www.example.com/', $trait->getUrl($option));
    }

    #[Test]
    public function generateUrl()
    {
        $trait = new FacetUrlTraitTestClass();

        $searchUriBuilder = $this->getSearchUriBuilder();
        $searchUriBuilder->expects(self::once())->method('getSetFacetValueUri')->with(
            self::isInstanceOf(SearchRequest::class),
            'foo_bar',
            'bar'
        )->willReturn('https://www.example.com/');

        $option = $this->getMockBuilder(AbstractFacetItem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $option->method('getFacet')->willReturn(
            $this->getFacet()
        );
        $option->method('getUriValue')->willReturn('bar');

        $this->registerEventUrlEvent(GenerateFacetItemUrlEvent::class, $option, '');

        self::assertEquals('https://www.example.com/', $trait->getUrl($option));
    }

    #[Test]
    public function dispatchGenerateFacetResetUrlEventForResetUrlGeneration()
    {
        $trait = new FacetUrlTraitTestClass();

        $facet = $this->getFacet();

        $this->registerEventUrlEvent(GenerateFacetResetUrlEvent::class, $facet, 'https://www.example.com/');
        self::assertEquals('https://www.example.com/', $trait->getResetUrl($facet));
    }

    #[Test]
    public function generateResetUrl()
    {
        $trait = new FacetUrlTraitTestClass();

        $facet = $this->getFacet();
        $facet->method('getName')->willReturn('foo_bar');

        $searchUriBuilder = $this->getSearchUriBuilder();

        $searchUriBuilder->expects(self::once())->method('getRemoveFacetUri')->with(
            self::isInstanceOf(SearchRequest::class),
            'foo_bar',
        )->willReturn('https://www.example.com/');

        $this->registerEventUrlEvent(GenerateFacetResetUrlEvent::class, $facet, '');

        self::assertEquals('https://www.example.com/', $trait->getResetUrl($facet));
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
