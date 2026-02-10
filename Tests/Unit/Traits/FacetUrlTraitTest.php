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

final class FacetUrlTraitTest extends UnitTestCase
{
    #[Test]
    public function dispatchGenerateFacetItemUrlEventForUrlGeneration(): void
    {
        $trait = new FacetUrlTraitTestClass();

        $option = $this->createStub(AbstractFacetItem::class);

        $this->registerEventUrlEvent(GenerateFacetItemUrlEvent::class, $option, 'https://www.example.com/');
        $this->assertSame('https://www.example.com/', $trait->getUrl($option));
    }

    #[Test]
    public function generateUrl(): void
    {
        $trait = new FacetUrlTraitTestClass();

        $searchUriBuilder = $this->getSearchUriBuilder();
        $searchUriBuilder
            ->expects($this->once())
            ->method('getSetFacetValueUri')
            ->with(self::isInstanceOf(SearchRequest::class), 'foo_bar', 'bar')
            ->willReturn('https://www.example.com/');
        $trait->setSearchUriBuilder($searchUriBuilder);

        $option = $this->createMock(AbstractFacetItem::class);
        $option->method('getFacet')->willReturn($this->getFacet());
        $option->method('getUriValue')->willReturn('bar');

        $this->registerEventUrlEvent(GenerateFacetItemUrlEvent::class, $option, '');

        $this->assertSame('https://www.example.com/', $trait->getUrl($option));
    }

    #[Test]
    public function dispatchGenerateFacetResetUrlEventForResetUrlGeneration(): void
    {
        $trait = new FacetUrlTraitTestClass();

        $facet = $this->getFacet();

        $this->registerEventUrlEvent(GenerateFacetResetUrlEvent::class, $facet, 'https://www.example.com/');
        $this->assertSame('https://www.example.com/', $trait->getResetUrl($facet));
    }

    #[Test]
    public function generateResetUrl(): void
    {
        $trait = new FacetUrlTraitTestClass();

        $facet = $this->getFacet();
        $facet->method('getName')->willReturn('foo_bar');

        $searchUriBuilder = $this->getSearchUriBuilder();

        $searchUriBuilder
            ->expects($this->once())
            ->method('getRemoveFacetUri')
            ->with(self::isInstanceOf(SearchRequest::class), 'foo_bar')
            ->willReturn('https://www.example.com/');
        $trait->setSearchUriBuilder($searchUriBuilder);

        $this->registerEventUrlEvent(GenerateFacetResetUrlEvent::class, $facet, '');

        $this->assertSame('https://www.example.com/', $trait->getResetUrl($facet));
    }

    private function getFacet(): OptionsFacet&MockObject
    {
        $facet = $this->createMock(OptionsFacet::class);
        $facet->method('getName')->willReturn('foo_bar');

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($this->createStub(SearchRequest::class));

        $facet->method('getResultSet')->willReturn($searchResultSet);

        return $facet;
    }

    private function getSearchUriBuilder(): SearchUriBuilder&MockObject
    {
        return $this->createMock(SearchUriBuilder::class);
    }

    private function registerEventUrlEvent(string $className, ...$args): EventDispatcherInterface&MockObject
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf($className))
            ->willReturn(new $className(...$args));

        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);

        return $eventDispatcher;
    }
}
