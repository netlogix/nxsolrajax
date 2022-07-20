<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Functional\Domain\Search\ResultSet;

use ApacheSolrForTypo3\Solr\Domain\Search\Query\Query;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Spellchecking\Suggestion;
use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequest;
use ApacheSolrForTypo3\Solr\Search;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SearchResultSet;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

class SearchResultSetTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = ['typo3conf/ext/solr'];

    protected int $pageUid = 1;

    public function setUp(): void
    {
        parent::setUp();

        $this->importDataSet('ntf://Database/pages.xml');
        $this->setUpFrontendRootPage($this->pageUid);

        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest(uniqid('https://www.example.com/'), 'GET');

        $mockEnvService = $this->getMockBuilder(EnvironmentService::class)
            ->onlyMethods(['isEnvironmentInFrontendMode'])
            ->getMock();
        $mockEnvService->method('isEnvironmentInFrontendMode')->willReturn(true);
        GeneralUtility::setSingletonInstance(EnvironmentService::class, $mockEnvService);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        GeneralUtility::purgeInstances();
        unset($GLOBALS['TYPO3_REQUEST']);
    }

    /**
     * @test
     * @return void
     */
    public function itCanGenerateResetUrl()
    {
        $subject = new SearchResultSet();

        $res = $subject->getResetUrl();
        self::assertEquals('', $res);
    }

    /**
     * @test
     * @return void
     */
    public function itCanGenerateNextUrl()
    {
        $currentPage = rand(1, 9999);

        $searchRequest = new SearchRequest([], $this->pageUid, 0, new TypoScriptConfiguration([], $this->pageUid));
        $searchRequest->setPage($currentPage);
        $subject = new SearchResultSet();
        $subject->setUsedResultsPerPage(10);
        $subject->setUsedSearchRequest($searchRequest);

        $usedSearch = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResultOffset'])
            ->getMock();
        $usedSearch->method('getResultOffset')->willReturn(0);
        $subject->setUsedSearch($usedSearch);

        // add two pages worth of results
        $subject->setAllResultCount(($currentPage * 10) + (2 * 10));

        $res = $subject->getNextUrl();
        self::assertEquals(sprintf('/?tx_solr%%5Bpage%%5D=%d', $currentPage + 1), $res);
    }

    /**
     * @test
     * @return void
     */
    public function itCanGeneratePrevUrl()
    {
        $currentPage = rand(5, 9999);

        $searchRequest = new SearchRequest([], $this->pageUid, 0, new TypoScriptConfiguration([], $this->pageUid));
        $searchRequest->setPage($currentPage);
        $subject = new SearchResultSet();
        $subject->setUsedSearchRequest($searchRequest);

        $usedSearch = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResultOffset'])
            ->getMock();
        $usedSearch->method('getResultOffset')->willReturn(0);
        $subject->setUsedSearch($usedSearch);

        $res = $subject->getPrevUrl();
        self::assertEquals(sprintf('/?tx_solr%%5Bpage%%5D=%d', $currentPage - 1), $res);
    }

    /**
     * @test
     * @return void
     */
    public function itCanGenerateFirstUrl()
    {
        $currentPage = rand(5, 9999);

        $searchRequest = new SearchRequest([], $this->pageUid, 0, new TypoScriptConfiguration([], $this->pageUid));
        $searchRequest->setPage($currentPage);
        $subject = new SearchResultSet();
        $subject->setUsedSearchRequest($searchRequest);

        $usedSearch = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResultOffset'])
            ->getMock();
        $usedSearch->method('getResultOffset')->willReturn(0);
        $subject->setUsedSearch($usedSearch);

        $res = $subject->getFirstUrl();
        self::assertEquals('/?tx_solr%5Bpage%5D=1', $res);
    }

    /**
     * @test
     * @return void
     */
    public function itCanGenerateLastUrl()
    {
        $currentPage = rand(5, 9999);

        $searchRequest = new SearchRequest([], $this->pageUid, 0, new TypoScriptConfiguration([], $this->pageUid));
        $searchRequest->setPage($currentPage);
        $subject = new SearchResultSet();
        $subject->setUsedResultsPerPage(10);
        $subject->setUsedSearchRequest($searchRequest);

        $usedSearch = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResultOffset'])
            ->getMock();
        $usedSearch->method('getResultOffset')->willReturn(0);
        $subject->setUsedSearch($usedSearch);

        // add two pages worth of results
        $subject->setAllResultCount(($currentPage * 10) + (2 * 10));

        $res = $subject->getLastUrl();
        self::assertEquals(sprintf('/?tx_solr%%5Bpage%%5D=%d', $currentPage + 2), $res);
    }

    /**
     * @test
     * @return void
     */
    public function itCanGenerateSearchUrl()
    {
        $searchRequest = new SearchRequest([], $this->pageUid, 0, new TypoScriptConfiguration([], $this->pageUid));
        $subject = new SearchResultSet();
        $subject->setUsedSearchRequest($searchRequest);

        $usedSearch = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $subject->setUsedSearch($usedSearch);


        $res = $subject->getSearchUrl();
        self::assertEquals('/?tx_solr%5Bq%5D=%7Bquery%7D', $res);
    }

    /**
     * @test
     * @return void
     */
    public function itCanGenerateSuggestUrl()
    {
        $searchRequest = new SearchRequest([], $this->pageUid, 0, new TypoScriptConfiguration([], $this->pageUid));
        $subject = new SearchResultSet();
        $subject->setUsedSearchRequest($searchRequest);

        $usedSearch = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $subject->setUsedSearch($usedSearch);


        $res = $subject->getSuggestUrl();
        self::assertEquals('/?type=1471261352', $res);
    }

    /**
     * @test
     * @return void
     */
    public function itCanGenerateSuggestionUrlIfItHasSuggestions()
    {
        $suggestion = uniqid('suggestion_');

        $searchRequest = new SearchRequest([], $this->pageUid, 0, new TypoScriptConfiguration([], $this->pageUid));
        $subject = new SearchResultSet();
        $subject->addSpellCheckingSuggestion(
            new Suggestion($suggestion)
        );
        $subject->setUsedSearchRequest($searchRequest);

        $usedSearch = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $subject->setUsedSearch($usedSearch);


        $res = $subject->getSuggestionUrl();
        self::assertEquals(sprintf('/?tx_solr%%5Bq%%5D=%s', $suggestion), $res);
    }

    /**
     * @test
     * @return void
     */
    public function itGeneratesEmptySuggestionUrlIfItHasNoSuggestions()
    {
        $searchRequest = new SearchRequest([], $this->pageUid, 0, new TypoScriptConfiguration([], $this->pageUid));
        $subject = new SearchResultSet();
        // do not add any suggester results here
        $subject->setUsedSearchRequest($searchRequest);

        $usedSearch = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $subject->setUsedSearch($usedSearch);


        $res = $subject->getSuggestionUrl();
        self::assertEquals('', $res);
    }


    /**
     * @test
     * @return void
     */
    public function itCanReturnFoundSuggestions()
    {
        $suggestion = uniqid('suggestion_');

        $searchRequest = new SearchRequest([], $this->pageUid, 0, new TypoScriptConfiguration([], $this->pageUid));
        $subject = new SearchResultSet();
        $subject->addSpellCheckingSuggestion(
            new Suggestion($suggestion)
        );
        $subject->setUsedSearchRequest($searchRequest);

        $usedSearch = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $subject->setUsedSearch($usedSearch);


        $res = $subject->getSuggestion();
        self::assertEquals($suggestion, $res);
    }

    /**
     * @test
     * @return void
     */
    public function itReturnsEmptyForNoSuggestionsFound()
    {
        $searchRequest = new SearchRequest([], $this->pageUid, 0, new TypoScriptConfiguration([], $this->pageUid));
        $subject = new SearchResultSet();
        $subject->setUsedSearchRequest($searchRequest);

        $usedSearch = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $subject->setUsedSearch($usedSearch);


        $res = $subject->getSuggestion();
        self::assertEquals('', $res);
    }

    /**
     * @test
     * @return void
     */
    public function itCanBeSerializedToJSON() {
        $currentPage = rand(5, 9999);
        $query = uniqid('foo:');
        $suggestion = uniqid('suggestion_');

        $searchRequest = new SearchRequest([], $this->pageUid, 0, new TypoScriptConfiguration([], $this->pageUid));
        $searchRequest->setPage($currentPage);
        $subject = new SearchResultSet();
        $subject->setUsedResultsPerPage(10);
        $subject->setUsedSearchRequest($searchRequest);

        $usedSearch = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResultOffset'])
            ->getMock();
        $usedSearch->method('getResultOffset')->willReturn(0);
        $subject->setUsedSearch($usedSearch);

        // add two pages worth of results
        $subject->setAllResultCount(($currentPage * 10) + (2 * 10));

        $subject->addSpellCheckingSuggestion(
            new Suggestion($suggestion)
        );

        $subject->setUsedQuery((new Query())->setQuery($query));

        $jsonString = json_encode($subject);
        self::assertEquals(JSON_ERROR_NONE, json_last_error());

        $jsonData = json_decode($jsonString, true);
        self::assertEquals(JSON_ERROR_NONE, json_last_error());

        self::assertIsString($jsonData['search']['q']);
        self::assertEquals($query, $jsonData['search']['q']);

        self::assertIsString($jsonData['search']['suggestion']);
        self::assertEquals($suggestion, $jsonData['search']['suggestion']);

        self::assertIsString($jsonData['search']['links']['search']);
        self::assertNotEmpty($jsonData['search']['links']['search']);

        self::assertIsString($jsonData['search']['links']['suggest']);
        self::assertNotEmpty($jsonData['search']['links']['suggest']);

        self::assertIsString($jsonData['search']['links']['suggestion']);
        self::assertNotEmpty($jsonData['search']['links']['suggestion']);

    }
}