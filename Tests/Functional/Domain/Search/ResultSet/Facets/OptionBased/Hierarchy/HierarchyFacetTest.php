<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Functional\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequest;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\LinkHelper\ResetLinkHelperInterface;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\HierarchyFacet;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

class HierarchyFacetTest extends FunctionalTestCase
{

    protected $testExtensionsToLoad = ['typo3conf/ext/solr','typo3conf/ext/nxsolrajax'];

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
    public function itUsesLinkHelperToGenerateResetUrl()
    {
        $link = uniqid('https://www.example.com/');

        $mockLinkHelper = $this->getMockBuilder(ResetLinkHelperInterface::class)
            ->onlyMethods(['canHandleResetLink', 'renderResetLink'])
            ->getMock();
        $mockLinkHelper->expects(self::once())->method('canHandleResetLink')->willReturn(true);
        $mockLinkHelper->expects(self::once())->method('renderResetLink')->willReturn($link);

        $config = ['linkHelper' => get_class($mockLinkHelper)];
        GeneralUtility::addInstance(get_class($mockLinkHelper), $mockLinkHelper);

        $resultSet = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject = GeneralUtility::makeInstance(
            HierarchyFacet::class,
            $resultSet,
            uniqid('name_'),
            uniqid('field_'),
            uniqid('label_'),
            $config
        );
        $subject->injectObjectManager(GeneralUtility::makeInstance(ObjectManager::class));

        $res = $subject->getResetUrl();

        self::assertEquals($link, $res);
    }


    /**
     * @test
     * @return void
     */
    public function itUsesDefaultUriBuilderIfNoLinkHelperIsSet()
    {
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest(uniqid('https://www.example.com/'), 'GET');

        $mockEnvService = $this->getMockBuilder(EnvironmentService::class)
            ->onlyMethods(['isEnvironmentInFrontendMode'])
            ->getMock();
        $mockEnvService->method('isEnvironmentInFrontendMode')->willReturn(true);
        GeneralUtility::setSingletonInstance(EnvironmentService::class, $mockEnvService);

        $pageUid = 1;
        $this->importDataSet('ntf://Database/pages.xml');
        $this->setUpFrontendRootPage($pageUid);


        $mockLinkHelper = $this->getMockBuilder(ResetLinkHelperInterface::class)
            ->onlyMethods(['canHandleResetLink', 'renderResetLink'])
            ->getMock();
        $mockLinkHelper->expects(self::once())->method('canHandleResetLink')->willReturn(false);
        $mockLinkHelper->expects(self::never())->method('renderResetLink');

        $config = ['linkHelper' => get_class($mockLinkHelper)];
        GeneralUtility::addInstance(get_class($mockLinkHelper), $mockLinkHelper);


        $currentFacetName = uniqid('name_');
        $currentFacetValue = uniqid('value_');

        $searchRequest = new SearchRequest(['q' => uniqid('q_')], $pageUid, 0, new TypoScriptConfiguration([], $pageUid)
        );
        $searchRequest->addFacetValue($currentFacetName, $currentFacetValue);

        // add some other facets so the url will not be empty
        $remainingFacetName = uniqid('name_');
        $remainingFacetValue = uniqid('value_');
        $searchRequest->addFacetValue($remainingFacetName, $remainingFacetValue);

        $resultSet = new SearchResultSet();
        $resultSet->setUsedSearchRequest($searchRequest);

        $subject = GeneralUtility::makeInstance(
            HierarchyFacet::class,
            $resultSet,
            $currentFacetName,
            uniqid('field_'),
            uniqid('label_'),
            $config
        );
        $subject->injectObjectManager(GeneralUtility::makeInstance(ObjectManager::class));

        $res = $subject->getResetUrl();

        self::assertIsString($res);
        self::assertNotEmpty($res);
        // check that the remaining filters are *not* removed
        self::assertStringContainsString(urlencode(sprintf('%s:%s', $remainingFacetName, $remainingFacetValue)), $res);
    }

}

