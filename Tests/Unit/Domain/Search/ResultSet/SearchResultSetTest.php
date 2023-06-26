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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SearchResultSetTest extends UnitTestCase
{

    protected bool $resetSingletonInstances = true;

    #[Test]
    public function itCanForceAddingFacetData(): void
    {
        $searchUriBuilder = $this->getMockBuilder(SearchUriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $uriBuilder = $this->getMockBuilder(UriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        GeneralUtility::addInstance(SearchUriBuilder::class, $searchUriBuilder);
        GeneralUtility::addInstance(UriBuilder::class, $uriBuilder);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);

        $searchRequest->method('getPage')->willReturn(10);
        self::assertFalse($searchResultSet->shouldAddFacetData());
        $searchResultSet->forceAddFacetData(true);
        self::assertTrue($searchResultSet->shouldAddFacetData());
    }

    #[Test]
    public function itIsAddingFacetDataOnPageOne(): void
    {
        $searchUriBuilder = $this->getMockBuilder(SearchUriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $uriBuilder = $this->getMockBuilder(UriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchRequest->method('getPage')->willReturn(10, 1);

        GeneralUtility::addInstance(SearchUriBuilder::class, $searchUriBuilder);
        GeneralUtility::addInstance(UriBuilder::class, $uriBuilder);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);
        self::assertFalse($searchResultSet->shouldAddFacetData());
        self::assertTrue($searchResultSet->shouldAddFacetData());
    }

    #[Test]
    public function itJsonSerialize(): void
    {
        $searchResultSetConverterService = $this->getMockBuilder(SearchResultSetConverterService::class)
            ->disableOriginalConstructor()
            ->getMock();

        GeneralUtility::setSingletonInstance(SearchResultSetConverterService::class, $searchResultSetConverterService);
        $searchResultSetConverterService->expects(self::once())->method('toArray')->willReturn([]);

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
        $this->assertEquals('asdfasdfasd', $searchResultSet->getSuggestion());
    }

    #[Test]
    public function getResetUrl(): void
    {
        $uriBuilder = $this->getMockBuilder(UriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $uriBuilder->method('reset')->willReturnSelf();
        $uriBuilder->method('build')->willReturn('https://www.example.com');
        GeneralUtility::addInstance(UriBuilder::class, $uriBuilder);

        $searchResultSet = new SearchResultSet();
        $this->assertEquals('https://www.example.com', $searchResultSet->getResetUrl());
    }

    #[Test]
    public function getSearchUrl(): void
    {
        $searchUriBuilder = $this->getMockBuilder(SearchUriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        GeneralUtility::addInstance(SearchUriBuilder::class, $searchUriBuilder);

        $searchUriBuilder->expects(self::once())->method('getNewSearchUri')->with(
            $searchRequest,
            '{query}'
        )->willReturn('https://www.example.com');

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);
        $this->assertEquals('https://www.example.com', $searchResultSet->getSearchUrl());
    }

    #[Test]
    public function getSuggestUrl(): void
    {
        $uriBuilder = $this->getMockBuilder(UriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $uriBuilder->method('reset')->willReturnSelf();
        $uriBuilder->method('setTargetPageType')->with('1471261352')->willReturnSelf();
        $uriBuilder->method('build')->willReturn('https://www.example.com');
        GeneralUtility::addInstance(UriBuilder::class, $uriBuilder);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->getSuggestUrl();
    }

    #[Test]
    public function getSuggestionUrlWithEmptySuggestions(): void
    {
        $searchUriBuilder = $this->getMockBuilder(SearchUriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchUriBuilder->expects(self::never())->method('getNewSearchUri');
        GeneralUtility::addInstance(SearchUriBuilder::class, $searchUriBuilder);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->getSuggestionUrl();
        $this->assertEquals('', $searchResultSet->getSuggestionUrl());
    }

    #[Test]
    public function getSuggestionUrl(): void
    {
        $searchUriBuilder = $this->getMockBuilder(SearchUriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        $searchUriBuilder->expects(self::once())->method('getNewSearchUri')->with(
            $searchRequest,
            'asdfasdfasd'
        )->willReturn('https://www.example.com?tx_solr[q]=asdfasdfasd');

        GeneralUtility::addInstance(SearchUriBuilder::class, $searchUriBuilder);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->addSpellCheckingSuggestion(new Suggestion('asdfasdfasd'));
        $searchResultSet->setUsedSearchRequest($searchRequest);
        $this->assertEquals('https://www.example.com?tx_solr[q]=asdfasdfasd', $searchResultSet->getSuggestionUrl());
    }

    #[Test]
    public function getFirstUrlOnFirstPageIsEmpty(): void
    {
        $searchUriBuilder = $this->getMockBuilder(SearchUriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchUriBuilder->expects(self::never())->method('getResultPageUri');

        GeneralUtility::addInstance(SearchUriBuilder::class, $searchUriBuilder);

        $searchRequest->method('getPage')->willReturn(1);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);
        $this->assertEquals('', $searchResultSet->getFirstUrl());
    }

    #[Test]
    public function getFirstUrl(): void
    {
        $searchUriBuilder = $this->getMockBuilder(SearchUriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchUriBuilder->expects(self::once())->method('getResultPageUri')->with(
            $searchRequest,
            1
        )->willReturn('https://www.example.com?tx_solr[page]=1');

        GeneralUtility::addInstance(SearchUriBuilder::class, $searchUriBuilder);

        $searchRequest->method('getPage')->willReturn(3);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);
        $this->assertEquals('https://www.example.com?tx_solr[page]=1', $searchResultSet->getFirstUrl());
    }

    #[Test]
    public function getPrevUrlOnFirstPageIsEmpty(): void
    {
        $searchUriBuilder = $this->getMockBuilder(SearchUriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchUriBuilder->expects(self::never())->method('getResultPageUri');

        GeneralUtility::addInstance(SearchUriBuilder::class, $searchUriBuilder);

        $searchRequest->method('getPage')->willReturn(1);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);
        $this->assertEquals('', $searchResultSet->getPrevUrl());
    }

    #[Test]
    public function getPrevUrl(): void
    {
        $searchUriBuilder = $this->getMockBuilder(SearchUriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchUriBuilder->expects(self::once())->method('getResultPageUri')->with(
            $searchRequest,
            2
        )->willReturn('https://www.example.com?tx_solr[page]=2');

        GeneralUtility::addInstance(SearchUriBuilder::class, $searchUriBuilder);

        $searchRequest->method('getPage')->willReturn(3);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);
        $this->assertEquals('https://www.example.com?tx_solr[page]=2', $searchResultSet->getPrevUrl());
    }

    #[Test]
    public function getNextUrlOnLastPageIsEmpty(): void
    {
        $searchUriBuilder = $this->getMockBuilder(SearchUriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchUriBuilder->expects(self::never())->method('getResultPageUri');

        GeneralUtility::addInstance(SearchUriBuilder::class, $searchUriBuilder);

        $searchRequest->method('getPage')->willReturn(1);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);
        $this->assertEquals('', $searchResultSet->getPrevUrl());
    }

    #[Test]
    public function getNextUrl(): void
    {
        $searchUriBuilder = $this->getMockBuilder(SearchUriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $search = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchUriBuilder->expects(self::once())->method('getResultPageUri')->with(
            $searchRequest,
            2
        )->willReturn('https://www.example.com?tx_solr[page]=2');

        GeneralUtility::addInstance(SearchUriBuilder::class, $searchUriBuilder);

        $searchRequest->method('getPage')->willReturn(1);
        $search->method('getResultOffset')->willReturn(10);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);
        $searchResultSet->setUsedSearch($search);
        $searchResultSet->setAllResultCount(50);
        $this->assertEquals('https://www.example.com?tx_solr[page]=2', $searchResultSet->getNextUrl());
    }

    #[Test]
    public function getLastUrlOnLastPageIsEmpty(): void
    {
        $searchUriBuilder = $this->getMockBuilder(SearchUriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $search = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchUriBuilder->expects(self::never())->method('getResultPageUri');

        GeneralUtility::addInstance(SearchUriBuilder::class, $searchUriBuilder);

        $searchRequest->method('getPage')->willReturn(1);
        $search->method('getResultOffset')->willReturn(10);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);
        $searchResultSet->setUsedSearch($search);
        $searchResultSet->setAllResultCount(10);
        $this->assertEquals('', $searchResultSet->getLastUrl());
    }

    #[Test]
    public function getLastUrl(): void
    {
        $searchUriBuilder = $this->getMockBuilder(SearchUriBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $search = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchUriBuilder->expects(self::once())->method('getResultPageUri')->with(
            $searchRequest,
            5
        )->willReturn('https://www.example.com?tx_solr[page]=5');

        GeneralUtility::addInstance(SearchUriBuilder::class, $searchUriBuilder);

        $searchRequest->method('getPage')->willReturn(1);
        $search->method('getResultOffset')->willReturn(10);

        $searchResultSet = new SearchResultSet();
        $searchResultSet->setUsedSearchRequest($searchRequest);
        $searchResultSet->setUsedSearch($search);
        $searchResultSet->setUsedResultsPerPage(10);
        $searchResultSet->setAllResultCount(50);
        $this->assertEquals('https://www.example.com?tx_solr[page]=5', $searchResultSet->getLastUrl());
    }

    #[Test]
    public function groupingIsDisabledByDefault(): void
    {
        $searchResultSet = new SearchResultSet();
        $this->assertFalse($searchResultSet->isGroupingEnabled());
    }

    protected function callMethod(object $object, string $method, array $args = []): mixed
    {
        $class = new \ReflectionClass($object);
        $reflectionMethod = $class->getMethod($method);
        $reflectionMethod->setAccessible(true);
        return $reflectionMethod->invokeArgs($object, $args);
    }
}
