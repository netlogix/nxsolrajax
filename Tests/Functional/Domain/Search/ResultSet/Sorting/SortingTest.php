<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Functional\Domain\Search\ResultSet\Sorting;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Sorting\Sorting as SortingAlias;
use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequest;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Sorting\Sorting;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

class SortingTest extends FunctionalTestCase
{

    protected $testExtensionsToLoad = ['typo3conf/ext/solr'];

    public function setUp(): void
    {
        parent::setUp();

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
    public function itCanGenerateUrl()
    {
        $pageUid = 1;
        $this->importDataSet('ntf://Database/pages.xml');
        $this->setUpFrontendRootPage($pageUid);


        $searchRequest = new SearchRequest([], $pageUid, 0, new TypoScriptConfiguration([], $pageUid));

        $resultSet = new SearchResultSet();
        $resultSet->setUsedSearchRequest($searchRequest);

        $dir = rand(0, 1) == 0 ? SortingAlias::DIRECTION_DESC : SortingAlias::DIRECTION_ASC;

        $subject = new Sorting($resultSet, uniqid('name_'), uniqid('field_'), $dir, uniqid('label_'), false, false);

        $res = $subject->getUrl();

        self::assertIsString($res);

        self::assertStringContainsString(sprintf('/?tx_solr%%5Bsort%%5D=%s+%s', $subject->getName(), $dir), $res);
    }

    /**
     * @test
     * @return void
     */
    public function itCanGenerateReverseUrl()
    {
        $pageUid = 1;
        $this->importDataSet('ntf://Database/pages.xml');
        $this->setUpFrontendRootPage($pageUid);


        $searchRequest = new SearchRequest([], $pageUid, 0, new TypoScriptConfiguration([], $pageUid));

        $resultSet = new SearchResultSet();
        $resultSet->setUsedSearchRequest($searchRequest);

        $dir = rand(0, 1) == 0 ? SortingAlias::DIRECTION_DESC : SortingAlias::DIRECTION_ASC;
        $inverse = $dir == SortingAlias::DIRECTION_DESC ? SortingAlias::DIRECTION_ASC : SortingAlias::DIRECTION_DESC;

        $subject = new Sorting($resultSet, uniqid('name_'), uniqid('field_'), $dir, uniqid('label_'), true, false);

        $res = $subject->getUrl();

        self::assertIsString($res);

        self::assertStringContainsString(sprintf('/?tx_solr%%5Bsort%%5D=%s+%s', $subject->getName(), $inverse), $res);
    }

    /**
     * @test
     * @return void
     */
    public function itCanGenerateResetUrl()
    {
        $pageUid = 1;
        $this->importDataSet('ntf://Database/pages.xml');
        $this->setUpFrontendRootPage($pageUid);


        $searchRequest = new SearchRequest([], $pageUid, 0, new TypoScriptConfiguration([], $pageUid));

        $resultSet = new SearchResultSet();
        $resultSet->setUsedSearchRequest($searchRequest);

        $dir = rand(0, 1) == 0 ? SortingAlias::DIRECTION_DESC : SortingAlias::DIRECTION_ASC;

        $subject = new Sorting($resultSet, uniqid('name_'), uniqid('field_'), $dir, uniqid('label_'), false, true);

        $res = $subject->getUrl();

        self::assertIsString($res);

        self::assertEquals('/', $res);
    }

    /**
     * @test
     * @return void
     */
    public function itCanBeSerialized()
    {
        $pageUid = 1;
        $this->importDataSet('ntf://Database/pages.xml');
        $this->setUpFrontendRootPage($pageUid);


        $searchRequest = new SearchRequest([], $pageUid, 0, new TypoScriptConfiguration([], $pageUid));

        $resultSet = new SearchResultSet();
        $resultSet->setUsedSearchRequest($searchRequest);

        $dir = rand(0, 1) == 0 ? SortingAlias::DIRECTION_DESC : SortingAlias::DIRECTION_ASC;

        $subject = new Sorting($resultSet, uniqid('name_'), uniqid('field_'), $dir, uniqid('label_'), false, false);

        $res = json_encode($subject);
        self::assertEquals(JSON_ERROR_NONE, json_last_error());

        $json = json_decode($res, true);
        self::assertEquals(JSON_ERROR_NONE, json_last_error());

        self::assertNotEmpty($json);

        self::assertArrayHasKey('direction', $json);
        self::assertEquals($dir, $json['direction']);

        self::assertArrayHasKey('label', $json);
        self::assertEquals($subject->getLabel(), $json['label']);

        self::assertArrayHasKey('resetOption', $json);
        self::assertEquals(false, $json['resetOption']);

        self::assertArrayHasKey('selected', $json);
        self::assertEquals(false, $json['selected']);

        self::assertArrayHasKey('url', $json);
        self::assertEquals(sprintf('/?tx_solr%%5Bsort%%5D=%s+%s', $subject->getName(), $dir), $json['url']);
    }
}

