<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Functional\Domain\Search\ResultSet\Facets\RangeBased\DateRange;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequest;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRangeFacet;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

class DateRangeFacetTest extends FunctionalTestCase
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
    public function itCanGetResetUrl()
    {
        $pageUid = 1;
        $this->importDataSet('ntf://Database/pages.xml');
        $this->setUpFrontendRootPage($pageUid);

        $searchRequest = new SearchRequest([], $pageUid, 0, new TypoScriptConfiguration([], $pageUid)
        );

        $resultSet = new SearchResultSet();
        $resultSet->setUsedSearchRequest($searchRequest);

        $subject = new DateRangeFacet($resultSet, uniqid('name_'), uniqid('field_'), uniqid('label_'), []);

        $res = $subject->getResetUrl();

        self::assertEquals('/', $res);
    }

}