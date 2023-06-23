<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Service;

use ApacheSolrForTypo3\Solr\Domain\Search\Query\Query;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\FacetCollection;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\HierarchyFacet;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRangeFacet;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Grouping\GroupCollection;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Grouping\GroupItemCollection;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Result\SearchResultCollection;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Sorting\Sorting;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Sorting\SortingCollection;
use ApacheSolrForTypo3\Solr\Search;
use ApacheSolrForTypo3\Solr\System\Solr\Document\Document;
use ApacheSolrForTypo3\Solr\System\Solr\ResponseAdapter;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping\Group;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SearchResultSet;
use Netlogix\Nxsolrajax\Service\SearchResultSetConverterService;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

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

    #[Test]
    public function itCanExportSearchResultSetToJson(): void
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

    #[Test]
    public function itThrowsExceptionOnFailedJsonExport(): void
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

    #[Test]
    public function itAddsBasicResultDataIfNoSearchResponseExists(): void
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

    #[Test]
    public function itWillRunThroughEnrichmentChainOnExporting(): void
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

    #[Test]
    public function itWillEnrichDocumentsWithHighlightedContent(): void
    {
        $documentId = uniqid('documentID_');
        $highlightString = uniqid('highlight_');

        $highlightedContent = new \stdClass();
        $highlightedContent->$documentId = new \stdClass();
        $highlightedContent->$documentId->content = [$highlightString];

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

        $searchResultSetConverterService = new SearchResultSetConverterService();
        $this->callMethod($searchResultSetConverterService, 'highlightSearchResults', [$searchResultSet]);

        $documents = $searchResultSet->getSearchResults();
        self::assertCount(1, $documents);
        /** @var Document $doc */
        $doc = $documents->getByPosition(0);

        self::assertEquals($highlightString, $doc->highlightedContent);
    }

    #[Test]
    public function itWillNotEnrichDocumentsWithHighlightedContentIfNoneExists(): void
    {
        $searchResultSet = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $searchResultSet->setSearchResults(new SearchResultCollection([new Document(['id' => uniqid('documentID_')])]));

        $usedSearch = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->getMock();

        $searchResultSet->setUsedSearch($usedSearch);

        $searchResultSetConverterService = new SearchResultSetConverterService();
        $this->callMethod($searchResultSetConverterService, 'highlightSearchResults', [$searchResultSet]);

        $documents = $searchResultSet->getSearchResults();
        self::assertCount(1, $documents);
        /** @var Document $doc */
        $doc = $documents->getByPosition(0);

        self::assertNull($doc->highlightedContent);
    }

    #[Test]
    public function itAddsNavigationLinksToResults(): void
    {
        $data = $this->mockedBasicData;
        $firstUrl = uniqid('https://www.example.com/first/');
        $prevUrl = uniqid('https://www.example.com/prev/');
        $nextUrl = uniqid('https://www.example.com/next/');
        $lastUrl = uniqid('https://www.example.com/last/');

        $searchResultSetMock = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFirstUrl', 'getPrevUrl', 'getNextUrl', 'getLastUrl'])
            ->getMock();
        $searchResultSetMock->method('getFirstUrl')->willReturn($firstUrl);
        $searchResultSetMock->method('getPrevUrl')->willReturn($prevUrl);
        $searchResultSetMock->method('getNextUrl')->willReturn($nextUrl);
        $searchResultSetMock->method('getLastUrl')->willReturn($lastUrl);

        $searchResultSetConverterService = new SearchResultSetConverterService();
        $result = $this->callMethod(
            $searchResultSetConverterService,
            'addLinksToSearchResultData',
            [$data, $searchResultSetMock]
        );

        self::assertEquals($firstUrl, $result['search']['links']['first']);
        self::assertEquals($prevUrl, $result['search']['links']['prev']);
        self::assertEquals($nextUrl, $result['search']['links']['next']);
        self::assertEquals($lastUrl, $result['search']['links']['last']);
    }

    #[Test]
    public function itAddsQueryDataToResults(): void
    {
        $query = uniqid('query_');
        $usedResultsPerPage = rand(1, 10);
        $offset = rand(1, 10);
        $resultCount = rand(100, 99999);

        $data = $this->mockedBasicData;

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

        $searchResultSetConverterService = new SearchResultSetConverterService();
        $result = $this->callMethod(
            $searchResultSetConverterService,
            'addQueryToSearchResultData',
            [$data, $searchResultSetMock]
        );

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

    #[Test]
    public function itAddsFacetDataToResults(): void
    {
        $facetName = uniqid('name_');

        $data = $this->mockedBasicData;

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
        );
        $facet->setIsAvailable(true);

        $facets = new FacetCollection();
        $facets->addFacet($facet);

        $this->inject($searchResultSetMock, 'facets', $facets);

        $searchResultSetConverterService = new SearchResultSetConverterService();
        $result = $this->callMethod(
            $searchResultSetConverterService,
            'addFacetsToSearchResultData',
            [$data, $searchResultSetMock]
        );

        self::assertArrayHasKey('facets', $result);
        self::assertNotEmpty($result['facets']);
        self::assertArrayHasKey($facetName, $result['facets']);

        self::assertSame($facet, $result['facets'][$facetName]);
    }

    #[Test]
    public function itWillNotAddSortingFromDataIfNoneIsUsed(): void
    {
        $data = $this->mockedBasicData;

        self::assertArrayNotHasKey('sortings', $data);

        $searchResultSetMock = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $this->inject($searchResultSetMock, 'sortings', new SortingCollection);

        $searchResultSetConverterService = new SearchResultSetConverterService();
        $result = $this->callMethod(
            $searchResultSetConverterService,
            'addSortingToSearchResultData',
            [$data, $searchResultSetMock]
        );

        self::assertArrayNotHasKey('sortings', $result);
    }

    #[Test]
    public function itWAddsSortingFromDataIfNoneIsUsed(): void
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

        self::assertArrayNotHasKey('sortings', $data);

        $searchResultSetMock = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $this->inject($searchResultSetMock, 'sortings', new SortingCollection);

        $searchResultSetMock->getSortings()->addSorting($sorting);

        $searchResultSetConverterService = new SearchResultSetConverterService();
        $result = $this->callMethod(
            $searchResultSetConverterService,
            'addSortingToSearchResultData',
            [$data, $searchResultSetMock]
        );

        self::assertArrayHasKey('sortings', $result);
        self::assertArrayHasKey($sortingName, $result['sortings']);
        self::assertEquals($sorting->getDirection(), $result['sortings'][$sortingName]->getDirection());
    }

    #[Test]
    public function itDoesNotGroupSearchResultsIfNotEnabled(): void
    {
        $document = new Document(['id' => uniqid('documentID_')]);

        $data = $this->mockedBasicData;

        $searchResultSetMock = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $searchResultSetMock->setSearchResults(new SearchResultCollection([$document]));

        $searchResultSetConverterService = new SearchResultSetConverterService();
        $result = $this->callMethod(
            $searchResultSetConverterService,
            'groupSearchResultData',
            [$data, $searchResultSetMock]
        );

        self::assertArrayHasKey('result', $result);
        self::assertIsArray($result['result']);
        self::assertArrayHasKey('items', $result['result']);
        self::assertNotEmpty($result['result']['items']);

        self::assertEquals($document, $result['result']['items'][0]);
    }

    #[Test]
    public function itGroupsSearchResultsIfEnabled(): void
    {
        $document = new Document(['id' => uniqid('documentID_')]);

        $data = $this->mockedBasicData;

        $group = new Group(uniqid('groupName_'), 10);
        $group->setGroupItems(new GroupItemCollection([$document]));

        $groupCollection = new GroupCollection([$group]);

        $searchResults = $this->getMockBuilder(SearchResultCollection::class)
            ->setConstructorArgs([[$document]])
            ->getMock();
        $searchResults->method('getGroups')->willReturn($groupCollection);

        $searchResultSetMock = $this->getMockBuilder(SearchResultSet::class)->getMock();
        $searchResultSetMock->method('isGroupingEnabled')->willReturn(true);
        $searchResultSetMock->method('getFacets')->willReturn(
            new FacetCollection(
                [
                    new DateRangeFacet(
                        $searchResultSetMock,
                        'date_range_facet',
                        uniqid('field_'),
                        uniqid('label_'),
                        []
                    )
                ]
            )
        );
        $searchResultSetMock->method('getSearchResults')->willReturn($searchResults);

        $searchResultSetConverterService = new SearchResultSetConverterService();
        $result = $this->callMethod(
            $searchResultSetConverterService,
            'groupSearchResultData',
            [$data, $searchResultSetMock]
        );

        self::assertArrayHasKey('result', $result);
        self::assertIsArray($result['result']);
        self::assertArrayHasKey('groups', $result['result']);
        self::assertNotEmpty($result['result']['groups']);
        self::assertEquals($group, $result['result']['groups'][0]);
    }

    protected function inject($target, $name, $dependency)
    {
        if (! is_object($target)) {
            throw new \InvalidArgumentException('Wrong type for argument $target, must be object.', 1476107338);
        }

        $objectReflection = new \ReflectionObject($target);
        $methodNamePart = strtoupper($name[0]) . substr($name, 1);
        if ($objectReflection->hasMethod('set' . $methodNamePart)) {
            $methodName = 'set' . $methodNamePart;
            $target->$methodName($dependency);
        } elseif ($objectReflection->hasMethod('inject' . $methodNamePart)) {
            $methodName = 'inject' . $methodNamePart;
            $target->$methodName($dependency);
        } elseif ($objectReflection->hasProperty($name)) {
            $property = $objectReflection->getProperty($name);
            $property->setAccessible(true);
            $property->setValue($target, $dependency);
        } else {
            throw new \RuntimeException(
                'Could not inject ' . $name . ' into object of type ' . get_class($target),
                1476107339
            );
        }
    }

    protected function callMethod(object $object, string $method, array $args): mixed
    {
        $class = new \ReflectionClass($object);
        $reflectionMethod = $class->getMethod($method);
        $reflectionMethod->setAccessible(true);
        return $reflectionMethod->invokeArgs($object, $args);
    }
}
