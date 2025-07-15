<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Spellchecking\Suggestion;
use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequest;
use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use ApacheSolrForTypo3\Solr\Search;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SearchResultSet;
use Netlogix\Nxsolrajax\Service\SearchResultSetConverterService;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SearchResultSetTest extends UnitTestCase
{

    protected bool $resetSingletonInstances = true;
    protected SearchUriBuilder $searchUriBuilder;
    protected UriBuilder $uriBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->searchUriBuilder = $this->getMockBuilder(SearchUriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->uriBuilder = $this->getMockBuilder(UriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        GeneralUtility::addInstance(SearchUriBuilder::class, $this->searchUriBuilder);
        GeneralUtility::addInstance(UriBuilder::class, $this->uriBuilder);
    }

    #[Test]
    public function itCanForceAddingFacetData(): void
    {
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);

        $searchRequest->method('getPage')->willReturn(10);
        $this->assertFalse($searchResultSet->shouldAddFacetData());
        $searchResultSet->forceAddFacetData(true);
        $this->assertTrue($searchResultSet->shouldAddFacetData());
    }

    #[Test]
    public function itIsAddingFacetDataOnPageOne(): void
    {
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchRequest->method('getPage')->willReturn(1, 10);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);
        $this->assertTrue($searchResultSet->shouldAddFacetData());
        $this->assertFalse($searchResultSet->shouldAddFacetData());
    }

    #[Test]
    public function itJsonSerialize(): void
    {
        $searchResultSetConverterService = $this->getMockBuilder(SearchResultSetConverterService::class)
            ->disableOriginalConstructor()
            ->getMock();
        GeneralUtility::setSingletonInstance(
            SearchResultSetConverterService::class,
            $searchResultSetConverterService
        );
        $searchResultSetConverterService->expects($this->once())->method('setSearchUriBuilder')->willReturn($searchResultSetConverterService);
        $searchResultSetConverterService->expects($this->once())->method('toArray')->willReturn([]);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->jsonSerialize();
    }

    #[Test]
    public function getSuggestionWithEmptySuggestions(): void
    {
        $searchResultSet = new SearchResultSet();
        $this->assertEmpty($searchResultSet->getSuggestion());
    }

    #[Test]
    public function getSuggestionReturnFirstSuggestion(): void
    {
        $searchResultSet = new SearchResultSet();
        $searchResultSet->addSpellCheckingSuggestion(new Suggestion('asdfasdfasd'));
        $searchResultSet->addSpellCheckingSuggestion(new Suggestion('sfsdfgsdfgsdfg'));
        $this->assertSame('asdfasdfasd', $searchResultSet->getSuggestion());
    }

    #[Test]
    public function getResetUrl(): void
    {
        $this->uriBuilder->method('reset')->willReturnSelf();
        $this->uriBuilder->method('build')->willReturn('https://www.example.com');

        $searchResultSet = new SearchResultSet();
        $this->assertSame('https://www.example.com', $searchResultSet->getResetUrl());
    }

    #[Test]
    public function getSearchUrl(): void
    {
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchUriBuilder->expects($this->once())->method('getNewSearchUri')->with(
            $searchRequest,
            '{query}'
        )->willReturn('https://www.example.com');

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);
        $this->assertSame('https://www.example.com', $searchResultSet->getSearchUrl());
    }

    #[Test]
    public function getSuggestUrl(): void
    {
        $this->uriBuilder->method('reset')->willReturnSelf();
        $this->uriBuilder->method('setTargetPageType')->with('1471261352')->willReturnSelf();
        $this->uriBuilder->method('build')->willReturn('https://www.example.com');

        $searchResultSet = new SearchResultSet();
        $searchResultSet->getSuggestUrl();
    }

    #[Test]
    public function getSuggestionUrlWithEmptySuggestions(): void
    {
        $this->searchUriBuilder->expects($this->never())->method('getNewSearchUri');

        $searchResultSet = new SearchResultSet();
        $searchResultSet->getSuggestionUrl();
        $this->assertSame('', $searchResultSet->getSuggestionUrl());
    }

    #[Test]
    public function getSuggestionUrl(): void
    {
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchUriBuilder->expects($this->once())->method('getNewSearchUri')->with(
            $searchRequest,
            'asdfasdfasd'
        )->willReturn('https://www.example.com?tx_solr[q]=asdfasdfasd');


        $searchResultSet = new SearchResultSet();
        $searchResultSet->addSpellCheckingSuggestion(new Suggestion('asdfasdfasd'));
        $searchResultSet->setUsedSearchRequest($searchRequest);
        $this->assertSame('https://www.example.com?tx_solr[q]=asdfasdfasd', $searchResultSet->getSuggestionUrl());
    }

    #[Test]
    public function getFirstUrlOnFirstPageIsEmpty(): void
    {
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchUriBuilder->expects($this->never())->method('getResultPageUri');
        $searchRequest->method('getPage')->willReturn(1);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);
        $this->assertSame('', $searchResultSet->getFirstUrl());
    }

    #[Test]
    public function getFirstUrl(): void
    {
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchUriBuilder->expects($this->once())->method('getResultPageUri')->with(
            $searchRequest,
            1
        )->willReturn('https://www.example.com?tx_solr[page]=1');

        $searchRequest->method('getPage')->willReturn(3);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);
        $this->assertSame('https://www.example.com?tx_solr[page]=1', $searchResultSet->getFirstUrl());
    }

    #[Test]
    public function getPrevUrlOnFirstPageIsEmpty(): void
    {
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchUriBuilder->expects($this->never())->method('getResultPageUri');

        $searchRequest->method('getPage')->willReturn(1);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);
        $this->assertSame('', $searchResultSet->getPrevUrl());
    }

    #[Test]
    public function getPrevUrl(): void
    {
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchUriBuilder->expects($this->once())->method('getResultPageUri')->with(
            $searchRequest,
            2
        )->willReturn('https://www.example.com?tx_solr[page]=2');

        $searchRequest->method('getPage')->willReturn(3);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);
        $this->assertSame('https://www.example.com?tx_solr[page]=2', $searchResultSet->getPrevUrl());
    }

    #[Test]
    public function getNextUrlOnLastPageIsEmpty(): void
    {
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchUriBuilder->expects($this->never())->method('getResultPageUri');

        $searchRequest->method('getPage')->willReturn(1);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);
        $this->assertSame('', $searchResultSet->getPrevUrl());
    }

    #[Test]
    public function getNextUrl(): void
    {
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $search = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchUriBuilder->expects($this->once())->method('getResultPageUri')->with(
            $searchRequest,
            2
        )->willReturn('https://www.example.com?tx_solr[page]=2');

        $searchRequest->method('getPage')->willReturn(1);
        $search->method('getResultOffset')->willReturn(10);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);
        $searchResultSet->setUsedSearch($search);
        $searchResultSet->setAllResultCount(50);
        $this->assertSame('https://www.example.com?tx_solr[page]=2', $searchResultSet->getNextUrl());
    }

    #[Test]
    public function getLastUrlOnLastPageIsEmpty(): void
    {
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $search = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchUriBuilder->expects($this->never())->method('getResultPageUri');

        $searchRequest->method('getPage')->willReturn(1);
        $search->method('getResultOffset')->willReturn(10);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);
        $searchResultSet->setUsedSearch($search);
        $searchResultSet->setAllResultCount(10);
        $this->assertSame('', $searchResultSet->getLastUrl());
    }

    #[Test]
    public function getLastUrl(): void
    {
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $search = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchUriBuilder->expects($this->once())->method('getResultPageUri')->with(
            $searchRequest,
            5
        )->willReturn('https://www.example.com?tx_solr[page]=5');

        $searchRequest->method('getPage')->willReturn(1);
        $search->method('getResultOffset')->willReturn(10);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);
        $searchResultSet->setUsedSearch($search);
        $searchResultSet->setUsedResultsPerPage(10);
        $searchResultSet->setAllResultCount(50);
        $this->assertSame('https://www.example.com?tx_solr[page]=5', $searchResultSet->getLastUrl());
    }

    #[Test]
    public function groupingIsDisabledByDefault(): void
    {
        $searchResultSet = new SearchResultSet();
        $this->assertFalse($searchResultSet->isGroupingEnabled());
    }

    protected function callMethod(object $object, string $method, array $args = []): mixed
    {
        $class = new ReflectionClass($object);
        $reflectionMethod = $class->getMethod($method);
        $reflectionMethod->setAccessible(true);
        return $reflectionMethod->invokeArgs($object, $args);
    }
}
