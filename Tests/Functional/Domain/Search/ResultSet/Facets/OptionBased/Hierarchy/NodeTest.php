<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Functional\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequest;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\LinkHelper\SelfLinkHelperInterface;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\HierarchyFacet;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\Node;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

class NodeTest extends FunctionalTestCase
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
    public function itUsesLinkHelperToGenerateUrl()
    {
        $link = uniqid('https://www.example.com/');

        $mockLinkHelper = $this->getMockBuilder(SelfLinkHelperInterface::class)
            ->onlyMethods(['canHandleSelfLink', 'renderSelfLink'])
            ->getMock();
        $mockLinkHelper->expects(self::once())->method('canHandleSelfLink')->willReturn(true);
        $mockLinkHelper->expects(self::once())->method('renderSelfLink')->willReturn($link);

        $config = ['linkHelper' => get_class($mockLinkHelper)];
        GeneralUtility::addInstance(get_class($mockLinkHelper), $mockLinkHelper);

        $resultSet = $this->getMockBuilder(SearchResultSet::class)
            ->disableOriginalConstructor()
            ->getMock();

        $facet = GeneralUtility::makeInstance(
            HierarchyFacet::class,
            $resultSet,
            uniqid('name_'),
            uniqid('field_'),
            uniqid('label_'),
            $config
        );

        $subject = new Node($facet, null, uniqid('key_'), uniqid('label_'), uniqid('value_'));

        $res = $subject->getUrl();

        self::assertEquals($link, $res);
    }

    /**
     * @test
     * @return void
     */
    public function itUsesDefaultUriBuilderIfNoLinkHelperIsSet()
    {
        $pageUid = 1;
        $this->importDataSet('ntf://Database/pages.xml');
        $this->setUpFrontendRootPage($pageUid);

        $searchRequest = new SearchRequest([], $pageUid, 0, new TypoScriptConfiguration([], $pageUid));

        $resultSet = new SearchResultSet();
        $resultSet->setUsedSearchRequest($searchRequest);

        $facet = GeneralUtility::makeInstance(
            HierarchyFacet::class,
            $resultSet,
            uniqid('name_'),
            uniqid('field_'),
            uniqid('label_'),
            []
        );

        $subject = new Node($facet, null, uniqid('key_'), uniqid('label_'), uniqid('value_'));

        $res = $subject->getUrl();
        self::assertIsString($res);
        self::assertNotEmpty($res);

        self::assertStringContainsString(urlencode(sprintf('%s:%s', $facet->getName(), $subject->getValue())), $res);
    }

}
