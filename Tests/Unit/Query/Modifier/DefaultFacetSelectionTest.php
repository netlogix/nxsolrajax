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

final class DefaultFacetSelectionTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function itDoesNotModifyRequestWhenFacetingIsDisabled(): void
    {
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $facetRegistry = $this->createStub(FacetRegistry::class);
        $searchRequest = $this->createStub(SearchRequest::class);
        $search = $this->createStub(Search::class);

        $typoScriptConfiguration = $this->createMock(TypoScriptConfiguration::class);
        $typoScriptConfiguration->method('getSearchFaceting')->willReturn(false);

        $query = new Query();
        $event = new AfterSearchQueryHasBeenPreparedEvent(
            query: $query,
            searchRequest: $searchRequest,
            search: $search,
            typoScriptConfiguration: $typoScriptConfiguration,
        );

        $defaultFacetSelection = new DefaultFacetSelection($queryBuilder, $facetRegistry);
        $defaultFacetSelection->__invoke($event);
    }

    #[Test]
    public function itDoesNotModifyRequestWhenFacetsAreEmpty(): void
    {
        $typoScriptConfiguration = $this->createMock(TypoScriptConfiguration::class);
        $typoScriptConfiguration->method('getSearchFaceting')->willReturn(true);
        $typoScriptConfiguration->method('getSearchFacetingFacets')->willReturn([]);
        $facetRegistry = $this->createStub(FacetRegistry::class);

        $queryBuilder = $this->createStub(QueryBuilder::class);

        $searchRequest = $this->createMock(SearchRequest::class);
        $searchRequest->method('getContextTypoScriptConfiguration')->willReturn($typoScriptConfiguration);

        $search = $this->createStub(Search::class);

        $query = new Query();
        $event = new AfterSearchQueryHasBeenPreparedEvent(
            query: $query,
            searchRequest: $searchRequest,
            search: $search,
            typoScriptConfiguration: $typoScriptConfiguration,
        );

        $defaultFacetSelection = new DefaultFacetSelection($queryBuilder, $facetRegistry);
        $defaultFacetSelection->__invoke($event);
    }

    #[Test]
    public function doNotAddFilterQueryWhenFacetValueHasNoResults(): void
    {
        $typoScriptConfiguration = $this->createMock(TypoScriptConfiguration::class);
        $typoScriptConfiguration->method('getSearchFaceting')->willReturn(true);
        $typoScriptConfiguration->method('getSearchFacetingFacets')->willReturn([
            [
                'includeInAvailableFacets' => 1,
                'defaultValue' => 'foo',
            ],
        ]);
        $typoScriptConfiguration->method('getSearchFacetingFacetByName')->willReturn([
            'field' => 'bar',
            'operator' => 'AND',
            'addFieldAsTag' => 1,
        ]);
        $facetRegistry = GeneralUtility::makeInstance(FacetRegistry::class);

        $queryBuilder = $this->createStub(QueryBuilder::class);

        $searchRequest = $this->createMock(SearchRequest::class);
        $searchRequest->method('getContextTypoScriptConfiguration')->willReturn($typoScriptConfiguration);

        $search = $this->createMock(Search::class);

        $search->method('search')->willReturn(new ResponseAdapter(''));
        $searchRequest->expects($this->never())->method('addFacetValue');

        $query = new Query();
        $event = new AfterSearchQueryHasBeenPreparedEvent(
            query: $query,
            searchRequest: $searchRequest,
            search: $search,
            typoScriptConfiguration: $typoScriptConfiguration,
        );

        $defaultFacetSelection = new DefaultFacetSelection($queryBuilder, $facetRegistry);
        $defaultFacetSelection->__invoke($event);

        $this->assertCount(0, $query->getFilterQueries());
    }

    #[Test]
    public function addDefaultValueAsFilterQuery(): void
    {
        $typoScriptConfiguration = $this->createMock(TypoScriptConfiguration::class);
        $typoScriptConfiguration->method('getSearchFaceting')->willReturn(true);
        $typoScriptConfiguration->method('getSearchFacetingFacets')->willReturn([
            'bar' => [
                'includeInAvailableFacets' => 1,
                'defaultValue' => 'foo',
            ],
        ]);
        $typoScriptConfiguration->method('getSearchFacetingFacetByName')->willReturn([
            'field' => 'bar',
            'operator' => 'AND',
            'addFieldAsTag' => 1,
        ]);
        $facetRegistry = GeneralUtility::makeInstance(FacetRegistry::class);

        $queryBuilder = $this->createStub(QueryBuilder::class);

        $searchRequest = $this->createMock(SearchRequest::class);
        $searchRequest->method('getContextTypoScriptConfiguration')->willReturn($typoScriptConfiguration);

        $search = $this->createMock(Search::class);

        $search->method('search')->willReturn(
            new ResponseAdapter(
                json_encode([
                    'response' => [
                        'numFound' => 2,
                    ],
                    'facets' => [
                        'count' => 4,
                    ],
                ]),
            ),
        );
        $searchRequest->expects($this->once())->method('addFacetValue')->with('bar', 'foo');

        $query = new Query();
        $event = new AfterSearchQueryHasBeenPreparedEvent(
            query: $query,
            searchRequest: $searchRequest,
            search: $search,
            typoScriptConfiguration: $typoScriptConfiguration,
        );

        $defaultFacetSelection = new DefaultFacetSelection($queryBuilder, $facetRegistry);
        $defaultFacetSelection->__invoke($event);

        $this->assertCount(1, $query->getFilterQueries());
    }

    #[Test]
    public function logWarningForInvalidFacetConfiguration(): void
    {
        $typoScriptConfiguration = $this->createMock(TypoScriptConfiguration::class);
        $typoScriptConfiguration->method('getSearchFaceting')->willReturn(true);
        $typoScriptConfiguration->method('getSearchFacetingFacets')->willReturn([
            [
                'includeInAvailableFacets' => 1,
                'defaultValue' => 'foo',
            ],
        ]);
        $typoScriptConfiguration->method('getSearchFacetingFacetByName')->willReturn([]);
        $facetRegistry = GeneralUtility::makeInstance(FacetRegistry::class);

        $queryBuilder = $this->createStub(QueryBuilder::class);

        $searchRequest = $this->createMock(SearchRequest::class);
        $searchRequest->method('getContextTypoScriptConfiguration')->willReturn($typoScriptConfiguration);

        $search = $this->createMock(Search::class);

        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->once())->method('warning');

        $search->method('search')->willReturn(new ResponseAdapter(''));
        $searchRequest->expects($this->never())->method('addFacetValue');

        $query = new Query();
        $event = new AfterSearchQueryHasBeenPreparedEvent(
            query: $query,
            searchRequest: $searchRequest,
            search: $search,
            typoScriptConfiguration: $typoScriptConfiguration,
        );

        $defaultFacetSelection = new DefaultFacetSelection($queryBuilder, $facetRegistry);
        $defaultFacetSelection->setLogger($logger);
        $defaultFacetSelection->__invoke($event);
    }
}
