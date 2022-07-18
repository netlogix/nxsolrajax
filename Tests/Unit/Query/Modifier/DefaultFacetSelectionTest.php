<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Query\Modifier;

use ApacheSolrForTypo3\Solr\Domain\Search\Query\Query;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\FacetRegistry;
use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequest;
use ApacheSolrForTypo3\Solr\Search;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use Netlogix\Nxsolrajax\Query\Modifier\DefaultFacetSelection;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;

class DefaultFacetSelectionTest extends UnitTestCase
{

    /**
     * @test
     * @return void
     */
    public function itDoesNotModifyRequestByDefault()
    {
        $query = new Query();
        $facetRegistry = new FacetRegistry();
        $searchMock = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->getMock();

        $searchRequestMock = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subject = new DefaultFacetSelection($facetRegistry);
        $subject->setSearch($searchMock);
        $subject->setSearchRequest($searchRequestMock);

        $res = $subject->modifyQuery($query);

        self::assertSame($query, $res);
    }

    /**
     * @test
     * @dataProvider defaultFacetSelectionsDataProvider
     * @return void
     */
    public function itCanGetDefaultFacetSelections(array $facetConfiguration, array $expected)
    {
        $configuration['plugin']['tx_solr']['search']['faceting']['facets'] = $facetConfiguration;

        $configuration = (new TypoScriptService())->convertPlainArrayToTypoScriptArray($configuration);

        $subject = $this->getAccessibleMock(DefaultFacetSelection::class, null, [], '', false);
        $subject->_set('configuration', new TypoScriptConfiguration($configuration));

        $res = $subject->_call('getDefaultFacetSelections');

        self::assertEquals($expected, $res);
    }

    public function defaultFacetSelectionsDataProvider(): array
    {
        $out = [];

        $defaultFacetName = uniqid('facet_');
        $defaultFacetValue = uniqid('defaultValue_');
        $conf[$defaultFacetName] = [
            'label' => uniqid('label_'),
            'field' => uniqid('field_'),
            'includeInAvailableFacets' => true,
            'defaultValue' => $defaultFacetValue
        ];

        $out['included facet with default value'] = [$conf, [$defaultFacetName => $defaultFacetValue]];

        unset($conf);
        $conf[uniqid('facet_')] = [
            'label' => uniqid('label_'),
            'field' => uniqid('field_'),
            'includeInAvailableFacets' => false,
            'defaultValue' => uniqid('defaultValue_')
        ];
        $out['not included facet'] = [$conf, []];

        unset($conf);
        $conf[uniqid('facet_')] = [
            'label' => uniqid('label_'),
            'field' => uniqid('field_'),
            'includeInAvailableFacets' => true
        ];
        $out['included facet without default'] = [$conf, []];

        return $out;
    }
}