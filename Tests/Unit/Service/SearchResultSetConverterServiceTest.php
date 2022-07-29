<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Service;

use ApacheSolrForTypo3\Solr\Domain\Search\Query\Query;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\FacetCollection;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\HierarchyFacet;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRangeFacet;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Grouping\Group;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Grouping\GroupCollection;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Grouping\GroupItemCollection;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Result\SearchResultCollection;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Sorting\Sorting;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Sorting\SortingCollection;
use ApacheSolrForTypo3\Solr\Search;
use ApacheSolrForTypo3\Solr\System\Solr\Document\Document;
use ApacheSolrForTypo3\Solr\System\Solr\ResponseAdapter;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SearchResultSet;
use Netlogix\Nxsolrajax\Service\SearchResultSetConverterService;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class SearchResultSetConverterServiceTest extends UnitTestCase
{

    protected $mockedBasicData = [
        'search' => [
            'q' => '*:*',
            'suggestion' => 'foo_suggestion',
            'links' => [
                'reset' => '',
                'first' => '',
                'prev' => '',
                'next' => '',
                'last' => '',
                'search' => 'https://www.example.com/search',
                'suggest' => 'https://www.example.com/suggest',
                'suggestion' => 'https://www.example.com/suggestion'
            ]
        ],
        'facets' => [],
        'result' => []
    ];

    /**
     * @test
     * @return void
     */
    public function itCanExportSearchResultSetToJson()
    {
        $searchResultSet = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject = $this->getMockBuilder(SearchResultSetConverterService::class)
            ->onlyMethods(['toArray'])
            ->getMock();
        $subject->expects(self::once())->method('toArray')->with($searchResultSet)->willReturn($this->mockedBasicData);

        $res = $subject->toJSON($searchResultSet);

        $json = json_decode($res, true);
        self::assertEquals(
            JSON_ERROR_NONE,
            json_last_error(),
            sprintf('unexpected JSON error: %s', json_last_error_msg())
        );
        self::assertEquals($this->mockedBasicData, $json);
    }

    /**
     * @test
     * @return void
     */
    public function itThrowsExceptionOnFailedJsonExport()
    {
        $this->expectExceptionCode(1659079186);

        $arrayWithReference = [
            'foo' => &$this->mockedBasicData,
            // this will cause json_encode to throw a recursion error
            'bar' => &$arrayWithReference
        ];

        $searchResultSet = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject = $this->getMockBuilder(SearchResultSetConverterService::class)
            ->onlyMethods(['toArray'])
            ->getMock();
        $subject->expects(self::once())->method('toArray')->with($searchResultSet)->willReturn($arrayWithReference);

        $subject->toJSON($searchResultSet);
    }

    /**
     * @test
     * @return void
     */
    public function itAddsBasicResultDataIfNoSearchResponseExists()
    {
        $subject = new SearchResultSetConverterService();

        $query = uniqid('query_');
        $resetUrl = uniqid('https://www.example.com/reset/');
        $searchUrl = uniqid('https://www.example.com/search/');
        $suggestUrl = uniqid('https://www.example.com/suggest/');
        $suggestionUrl = uniqid('https://www.example.com/suggestion/');

        $searchResultSetMock = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResponse', 'getResetUrl', 'getSearchUrl', 'getSuggestUrl', 'getSuggestionUrl'])
            ->getMock();
        $searchResultSetMock->method('getResponse')->willReturn(null);
        // url generation is tested elsewhere and can be safely mocked
        $searchResultSetMock->method('getResetUrl')->willReturn($resetUrl);
        $searchResultSetMock->method('getSearchUrl')->willReturn($searchUrl);
        $searchResultSetMock->method('getSuggestUrl')->willReturn($suggestUrl);
        $searchResultSetMock->method('getSuggestionUrl')->willReturn($suggestionUrl);

        $searchResultSetMock->setUsedQuery((new Query())->setQuery($query));


        $res = $subject->toArray($searchResultSetMock);

        self::assertArrayHasKey('search', $res);
        self::assertNotEmpty($res['search']);
        self::assertArrayHasKey('q', $res['search']);
        self::assertEquals($query, $res['search']['q']);
        // test if links are present
        self::assertArrayHasKey('links', $res['search']);
        self::assertArrayHasKey('reset', $res['search']['links']);
        self::assertEquals($resetUrl, $res['search']['links']['reset']);
        self::assertArrayHasKey('search', $res['search']['links']);
        self::assertEquals($searchUrl, $res['search']['links']['search']);
        self::assertArrayHasKey('suggest', $res['search']['links']);
        self::assertEquals($suggestUrl, $res['search']['links']['suggest']);
        self::assertArrayHasKey('suggestion', $res['search']['links']);
        self::assertEquals($suggestionUrl, $res['search']['links']['suggestion']);

        self::assertArrayHasKey('facets', $res);
        self::assertEmpty($res['facets']);

        self::assertArrayHasKey('result', $res);
        self::assertEmpty($res['result']);
    }

    /**
     * @test
     * @return void
     */
    public function itWillRunThroughEnrichmentChainOnExporting()
    {
        $mockedData = $this->mockedBasicData;

        $subject = $this->getMockBuilder(SearchResultSetConverterService::class)
            ->onlyMethods(
                [
                    'generateBasicSearchResultData',
                    'highlightSearchResults',
                    'addLinksToSearchResultData',
                    'addQueryToSearchResultData',
                    'addFacetsToSearchResultData',
                    'addSortingToSearchResultData',
                    'groupSearchResultData'
                ]
            )
            ->getMock();
        $subject->method('generateBasicSearchResultData')->willReturn($mockedData);

        $searchResultSetMock = $this->getMockBuilder(SearchResultSet::class)
            // the constructor relies on objectManager which is not present in this context
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $subject->expects(self::once())->method('highlightSearchResults')->with($searchResultSetMock);
        $subject->expects(self::once())->method('addLinksToSearchResultData')->with(
            $mockedData,
            $searchResultSetMock
        )->willReturn($mockedData);
        $subject->expects(self::once())->method('addQueryToSearchResultData')->with(
            $mockedData,
            $searchResultSetMock
        )->willReturn($mockedData);
        $subject->expects(self::once())->method('addFacetsToSearchResultData')->with(
            $mockedData,
            $searchResultSetMock
        )->willReturn($mockedData);
        $subject->expects(self::once())->method('addSortingToSearchResultData')->with(
            $mockedData,
            $searchResultSetMock
        )->willReturn($mockedData);
        $subject->expects(self::once())->method('groupSearchResultData')->with(
            $mockedData,
            $searchResultSetMock
        )->willReturn($mockedData);

        $response = new ResponseAdapter('');
        $searchResultSetMock->setResponse($response);

        $subject->toArray($searchResultSetMock);
    }

    /**
     * @test
     * @return void
     */
    public function itWillEnrichDocumentsWithHighlightedContent()
    {
        $documentId = uniqid('documentID_');
        $highlightString = uniqid('highlight_');

        $highlightedContent = new \stdClass();
        $highlightedContent->$documentId = new \stdClass();
        $highlightedContent->$documentId->content = [$highlightString];

        $subject = $this->getAccessibleMock(SearchResultSetConverterService::class, ['dummy']);

        $searchResultSet = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $searchResultSet->setSearchResults(new SearchResultCollection([new Document(['id' => $documentId])]));

        $usedSearch = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getHighlightedContent'])
            ->getMock();
        $usedSearch->method('getHighlightedContent')->willReturn($highlightedContent);

        $response = new ResponseAdapter('');
        $this->inject($usedSearch, 'response', $response);

        $searchResultSet->setUsedSearch($usedSearch);

        $subject->_call('highlightSearchResults', $searchResultSet);

        $documents = $searchResultSet->getSearchResults();
        self::assertCount(1, $documents);
        /** @var Document $doc */
        $doc = $documents->getByPosition(0);

        self::assertEquals($highlightString, $doc->highlightedContent);
    }

    /**
     * @test
     * @return void
     */
    public function itWillNotEnrichDocumentsWithHighlightedContentIfNoneExists()
    {
        $subject = $this->getAccessibleMock(SearchResultSetConverterService::class, ['dummy']);

        $searchResultSet = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $searchResultSet->setSearchResults(new SearchResultCollection([new Document(['id' => uniqid('documentID_')])]));

        $usedSearch = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->getMock();

        $searchResultSet->setUsedSearch($usedSearch);

        $subject->_call('highlightSearchResults', $searchResultSet);

        $documents = $searchResultSet->getSearchResults();
        self::assertCount(1, $documents);
        /** @var Document $doc */
        $doc = $documents->getByPosition(0);

        self::assertNull($doc->highlightedContent);
    }

    /**
     * @test
     * @return void
     */
    public function itAddsNavigationLinksToResults()
    {
        $data = $this->mockedBasicData;
        $firstUrl = uniqid('https://www.example.com/first/');
        $prevUrl = uniqid('https://www.example.com/prev/');
        $nextUrl = uniqid('https://www.example.com/next/');
        $lastUrl = uniqid('https://www.example.com/last/');

        $subject = $this->getAccessibleMock(SearchResultSetConverterService::class, ['dummy']);

        $searchResultSetMock = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFirstUrl', 'getPrevUrl', 'getNextUrl', 'getLastUrl'])
            ->getMock();
        $searchResultSetMock->method('getFirstUrl')->willReturn($firstUrl);
        $searchResultSetMock->method('getPrevUrl')->willReturn($prevUrl);
        $searchResultSetMock->method('getNextUrl')->willReturn($nextUrl);
        $searchResultSetMock->method('getLastUrl')->willReturn($lastUrl);

        $result = $subject->_call('addLinksToSearchResultData', $data, $searchResultSetMock);

        self::assertEquals($firstUrl, $result['search']['links']['first']);
        self::assertEquals($prevUrl, $result['search']['links']['prev']);
        self::assertEquals($nextUrl, $result['search']['links']['next']);
        self::assertEquals($lastUrl, $result['search']['links']['last']);
    }

    /**
     * @test
     * @return void
     */
    public function itAddsQueryDataToResults()
    {
        $query = uniqid('query_');
        $usedResultsPerPage = rand(1, 10);
        $offset = rand(1, 10);
        $resultCount = rand(100, 99999);

        $data = $this->mockedBasicData;
        $subject = $this->getAccessibleMock(SearchResultSetConverterService::class, ['dummy']);

        $searchResultSetMock = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $searchResultSetMock->setUsedQuery((new Query())->setQuery($query));
        $searchResultSetMock->setUsedResultsPerPage($usedResultsPerPage);
        $searchResultSetMock->setAllResultCount($resultCount);

        $usedSearch = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResultOffset'])
            ->getMock();
        $usedSearch->method('getResultOffset')->willReturn($offset);

        $searchResultSetMock->setUsedSearch($usedSearch);

        $result = $subject->_call('addQueryToSearchResultData', $data, $searchResultSetMock);

        self::assertArrayHasKey('q', $result['result']);
        self::assertEquals($query, $result['result']['q']);

        self::assertArrayHasKey('limit', $result['result']);
        self::assertEquals($usedResultsPerPage, $result['result']['limit']);

        self::assertArrayHasKey('offset', $result['result']);
        self::assertEquals($offset, $result['result']['offset']);

        self::assertArrayHasKey('totalResults', $result['result']);
        self::assertEquals($resultCount, $result['result']['totalResults']);

        self::assertArrayHasKey('items', $result['result']);
        self::assertEmpty($result['result']['items']);

        self::assertArrayHasKey('groups', $result['result']);
        self::assertEmpty($result['result']['groups']);
    }

    /**
     * @test
     * @return void
     */
    public function itAddsFacetDataToResults()
    {
        $facetName = uniqid('name_');

        $data = $this->mockedBasicData;
        $subject = $this->getAccessibleMock(SearchResultSetConverterService::class, ['dummy']);

        $searchResultSetMock = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $searchResultSetMock->forceAddFacetData(true);

        $facet = new HierarchyFacet(
            new \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet(),
            $facetName,
            uniqid('field_'),
            uniqid('label_'),
            [],
            $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock()
        );
        $facet->setIsAvailable(true);

        $facets = new FacetCollection();
        $facets->addFacet($facet);

        $this->inject($searchResultSetMock, 'facets', $facets);

        $result = $subject->_call('addFacetsToSearchResultData', $data, $searchResultSetMock);

        self::assertArrayHasKey('facets', $result);
        self::assertNotEmpty($result['facets']);
        self::assertArrayHasKey($facetName, $result['facets']);

        self::assertSame($facet, $result['facets'][$facetName]);
    }

    /**
     * @test
     * @return void
     */
    public function itWillNotAddSortingFromDataIfNoneIsUsed()
    {
        $data = $this->mockedBasicData;
        $subject = $this->getAccessibleMock(SearchResultSetConverterService::class, ['dummy']);

        self::assertArrayNotHasKey('sortings', $data);

        $searchResultSetMock = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $this->inject($searchResultSetMock, 'sortings', new SortingCollection);

        $result = $subject->_call('addSortingToSearchResultData', $data, $searchResultSetMock);

        self::assertArrayNotHasKey('sortings', $result);
    }

    /**
     * @test
     * @return void
     */
    public function itWAddsSortingFromDataIfNoneIsUsed()
    {
        $sortingName = uniqid('name_');

        $sorting = new Sorting(
            new \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet(),
            $sortingName,
            uniqid('field_'),
            rand(1, 2) % 2 ? Sorting::DIRECTION_ASC : Sorting::DIRECTION_DESC,
            uniqid('label_')
        );

        $data = $this->mockedBasicData;
        $subject = $this->getAccessibleMock(SearchResultSetConverterService::class, ['dummy']);

        self::assertArrayNotHasKey('sortings', $data);

        $searchResultSetMock = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $this->inject($searchResultSetMock, 'sortings', new SortingCollection);

        $searchResultSetMock->getSortings()->addSorting($sorting);

        $result = $subject->_call('addSortingToSearchResultData', $data, $searchResultSetMock);

        self::assertArrayHasKey('sortings', $result);
        self::assertArrayHasKey($sortingName, $result['sortings']);
        self::assertEquals($sorting->getDirection(), $result['sortings'][$sortingName]->getDirection());
    }

    /**
     * @test
     * @return void
     */
    public function itDoesNotGroupSearchResultsIfNotEnabled()
    {
        $document = new Document(['id' => uniqid('documentID_')]);

        $data = $this->mockedBasicData;
        $subject = $this->getAccessibleMock(SearchResultSetConverterService::class, ['dummy']);

        $searchResultSetMock = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $searchResultSetMock->setSearchResults(new SearchResultCollection([$document]));

        $result = $subject->_call('groupSearchResultData', $data, $searchResultSetMock);

        self::assertArrayHasKey('result', $result);
        self::assertIsArray($result['result']);
        self::assertArrayHasKey('items', $result['result']);
        self::assertNotEmpty($result['result']['items']);

        self::assertEquals($document, $result['result']['items'][0]);
    }

    /**
     * @test
     * @return void
     */
    public function itGroupsSearchResultsIfEnabled()
    {
        $document = new Document(['id' => uniqid('documentID_')]);

        $data = $this->mockedBasicData;
        $subject = $this->getAccessibleMock(SearchResultSetConverterService::class, ['dummy']);

        $searchResultSetMock = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $this->inject($searchResultSetMock, 'facets', new FacetCollection());

        $searchResultSetMock->addFacet(
            new DateRangeFacet(
                $searchResultSetMock,
                uniqid('name_'),
                uniqid('field_'),
                uniqid('label_'),
                [],
                $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock()
            )
        );

        $searchResults = new SearchResultCollection([$document]);

        $group = new Group(uniqid('groupName_'), 10);
        $group->setGroupItems(new GroupItemCollection([$document]));

        $groupCollection = new GroupCollection();
        $groupCollection->add($group);

        $searchResults->setGroups($groupCollection);

        $searchResultSetMock->setSearchResults($searchResults);

        $result = $subject->_call('groupSearchResultData', $data, $searchResultSetMock);

        self::assertArrayHasKey('result', $result);
        self::assertIsArray($result['result']);
        self::assertArrayHasKey('groups', $result['result']);
        self::assertNotEmpty($result['result']['groups']);

        self::assertEquals($group, $result['result']['groups'][0]);
    }
}