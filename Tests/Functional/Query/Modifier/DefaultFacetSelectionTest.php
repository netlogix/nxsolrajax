<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Functional\Query\Modifier;

use ApacheSolrForTypo3\Solr\Domain\Search\Query\Query;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\FacetRegistry;
use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequest;
use ApacheSolrForTypo3\Solr\Search;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use ApacheSolrForTypo3\Solr\System\Solr\ResponseAdapter;
use Netlogix\Nxsolrajax\Query\Modifier\DefaultFacetSelection;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Solarium\QueryType\Select\Query\FilterQuery;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class DefaultFacetSelectionTest extends FunctionalTestCase
{
    /**
     * @test
     * @return void
     */
    public function itDoesNotModifyQueryWithoutConfiguration()
    {
        $pageUid = 1;
        $config = [];
        $typoscriptConfiguration = new TypoScriptConfiguration($config, $pageUid);

        $facetRegistry = new FacetRegistry();

        $subject = new DefaultFacetSelection($facetRegistry);
        $this->inject($subject, 'configuration', $typoscriptConfiguration);

        $searchRequest = new SearchRequest([], $pageUid, 0, $typoscriptConfiguration);
        $subject->setSearchRequest($searchRequest);

        $query = new Query();

        $res = $subject->modifyQuery($query);

        self::assertSame($query, $res);
    }

    /**
     * @test
     * @return void
     */
    public function itAddsDefaultFacetsToQuery()
    {
        $pageUid = 1;

        $defaultFacetName = uniqid('facet_');

        $config = [
            'plugin' => [
                'tx_solr' => [
                    'search' => [
                        'faceting' => [
                            'facets' => [
                                $defaultFacetName => [
                                    'label' => uniqid('label_'),
                                    'field' => uniqid('field_'),
                                    'includeInAvailableFacets' => true,
                                    'defaultValue' => uniqid('default_')
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $config = (new TypoScriptService())->convertPlainArrayToTypoScriptArray($config);

        $typoscriptConfiguration = new TypoScriptConfiguration($config, $pageUid);

        $facetRegistry = new FacetRegistry();
        $facetRegistry->injectObjectManager(GeneralUtility::makeInstance(ObjectManager::class));

        $subject = new DefaultFacetSelection($facetRegistry);
        $this->inject($subject, 'configuration', $typoscriptConfiguration);

        $searchRequest = new SearchRequest([], $pageUid, 0, $typoscriptConfiguration);
        $subject->setSearchRequest($searchRequest);

        $searchMock = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['search'])
            ->getMock();
        // method performs a search for each possible filter query and includes the first one that returns results
        $searchMock->expects(self::once())->method('search')->willReturn(
            new ResponseAdapter(
                json_encode(
                    [
                        'facets' => [
                            'count' => 10
                        ],
                        'response' => [
                            'numFound' => 100
                        ]

                    ]
                )
            )
        );

        $subject->setSearch($searchMock);

        $query = new Query();

        self::assertNull($query->getFilterQuery('type'));

        $res = $subject->modifyQuery($query);

        self::assertInstanceOf(FilterQuery::class, $res->getFilterQuery('type'));
    }
}
