<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Query\Modifier;

use ApacheSolrForTypo3\Solr\Domain\Search\Query\Query;
use ApacheSolrForTypo3\Solr\Domain\Search\Query\QueryBuilder;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\FacetRegistry;
use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequest;
use ApacheSolrForTypo3\Solr\Event\Search\AfterSearchQueryHasBeenPreparedEvent;
use ApacheSolrForTypo3\Solr\Search;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use ApacheSolrForTypo3\Solr\System\Solr\ResponseAdapter;
use Netlogix\Nxsolrajax\Query\Modifier\DefaultFacetSelection;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DefaultFacetSelectionTest extends UnitTestCase
{

    protected bool $resetSingletonInstances = true;

    #[Test]
    public function itDoesNotModifyRequestWhenFacetingIsDisabled(): void
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $facetRegistry = $this->getMockBuilder(FacetRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $search = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->getMock();

        $typoScriptConfiguration = $this->getMockBuilder(TypoScriptConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $typoScriptConfiguration->method('getSearchFaceting')->willReturn(false);

        $query = new Query();
        $event = new AfterSearchQueryHasBeenPreparedEvent(
            query: $query,
            searchRequest: $searchRequest,
            search: $search,
            typoScriptConfiguration: $typoScriptConfiguration
        );

        $defaultFacetSelection = new DefaultFacetSelection($queryBuilder, $facetRegistry);
        $defaultFacetSelection->__invoke($event);
    }

    #[Test]
    public function itDoesNotModifyRequestWhenFacetsAreEmpty(): void
    {
        $typoScriptConfiguration = $this->getMockBuilder(TypoScriptConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $typoScriptConfiguration->method('getSearchFaceting')->willReturn(true);
        $typoScriptConfiguration->method('getSearchFacetingFacets')->willReturn([]);
        $facetRegistry = $this->getMockBuilder(FacetRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchRequest->method('getContextTypoScriptConfiguration')->willReturn($typoScriptConfiguration);

        $search = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->getMock();

        $query = new Query();
        $event = new AfterSearchQueryHasBeenPreparedEvent(
            query: $query,
            searchRequest: $searchRequest,
            search: $search,
            typoScriptConfiguration: $typoScriptConfiguration
        );

        $defaultFacetSelection = new DefaultFacetSelection($queryBuilder, $facetRegistry);
        $defaultFacetSelection->__invoke($event);
    }

    #[Test]
    public function doNotAddFilterQueryWhenFacetValueHasNoResults(): void
    {
        $typoScriptConfiguration = $this->getMockBuilder(TypoScriptConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $typoScriptConfiguration->method('getSearchFaceting')->willReturn(true);
        $typoScriptConfiguration->method('getSearchFacetingFacets')->willReturn([
            [
                'includeInAvailableFacets' => 1,
                'defaultValue' => 'foo',
            ]
        ]);
        $typoScriptConfiguration->method('getSearchFacetingFacetByName')->willReturn([
            'field' => 'bar',
            'operator' => 'AND',
            'addFieldAsTag' => 1,
        ]);
        $facetRegistry = GeneralUtility::makeInstance(FacetRegistry::class);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchRequest->method('getContextTypoScriptConfiguration')->willReturn($typoScriptConfiguration);

        $search = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->getMock();

        $search->method('search')->willReturn(new ResponseAdapter(''));
        $searchRequest->expects(self::never())->method('addFacetValue');

        $query = new Query();
        $event = new AfterSearchQueryHasBeenPreparedEvent(
            query: $query,
            searchRequest: $searchRequest,
            search: $search,
            typoScriptConfiguration: $typoScriptConfiguration
        );

        $defaultFacetSelection = new DefaultFacetSelection($queryBuilder, $facetRegistry);
        $defaultFacetSelection->__invoke($event);

        self::assertCount(0, $query->getFilterQueries());
    }

    #[Test]
    public function addDefaultValueAsFilterQuery(): void
    {
        $typoScriptConfiguration = $this->getMockBuilder(TypoScriptConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $typoScriptConfiguration->method('getSearchFaceting')->willReturn(true);
        $typoScriptConfiguration->method('getSearchFacetingFacets')->willReturn([
            'bar' => [
                'includeInAvailableFacets' => 1,
                'defaultValue' => 'foo',
            ]
        ]);
        $typoScriptConfiguration->method('getSearchFacetingFacetByName')->willReturn([
            'field' => 'bar',
            'operator' => 'AND',
            'addFieldAsTag' => 1,
        ]);
        $facetRegistry = GeneralUtility::makeInstance(FacetRegistry::class);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchRequest->method('getContextTypoScriptConfiguration')->willReturn($typoScriptConfiguration);

        $search = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->getMock();

        $search->method('search')->willReturn(new ResponseAdapter(json_encode([
            'response' => [
                'numFound' => 2,
            ],
            'facets' => [
                'count' => 4,
            ],
        ])));
        $searchRequest->expects(self::once())->method('addFacetValue')->with('bar', 'foo');

        $query = new Query();
        $event = new AfterSearchQueryHasBeenPreparedEvent(
            query: $query,
            searchRequest: $searchRequest,
            search: $search,
            typoScriptConfiguration: $typoScriptConfiguration
        );

        $defaultFacetSelection = new DefaultFacetSelection($queryBuilder, $facetRegistry);
        $defaultFacetSelection->__invoke($event);

        self::assertCount(1, $query->getFilterQueries());
    }

    #[Test]
    public function logWarningForInvalidFacetConfiguration(): void
    {
        $typoScriptConfiguration = $this->getMockBuilder(TypoScriptConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $typoScriptConfiguration->method('getSearchFaceting')->willReturn(true);
        $typoScriptConfiguration->method('getSearchFacetingFacets')->willReturn([
            [
                'includeInAvailableFacets' => 1,
                'defaultValue' => 'foo',
            ]
        ]);
        $typoScriptConfiguration->method('getSearchFacetingFacetByName')->willReturn([]);
        $facetRegistry = GeneralUtility::makeInstance(FacetRegistry::class);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $searchRequest = $this->getMockBuilder(SearchRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchRequest->method('getContextTypoScriptConfiguration')->willReturn($typoScriptConfiguration);

        $search = $this->getMockBuilder(Search::class)
            ->disableOriginalConstructor()
            ->getMock();

        $logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $logger->expects(self::once())->method('warning');

        $search->method('search')->willReturn(new ResponseAdapter(''));
        $searchRequest->expects(self::never())->method('addFacetValue');

        $query = new Query();
        $event = new AfterSearchQueryHasBeenPreparedEvent(
            query: $query,
            searchRequest: $searchRequest,
            search: $search,
            typoScriptConfiguration: $typoScriptConfiguration
        );

        $defaultFacetSelection = new DefaultFacetSelection($queryBuilder, $facetRegistry);
        $defaultFacetSelection->setLogger($logger);
        $defaultFacetSelection->__invoke($event);
    }
}
