<?php

namespace Netlogix\Nxsolrajax\Query\Modifier;

use ApacheSolrForTypo3\Solr\Domain\Search\Query\Query;
use ApacheSolrForTypo3\Solr\Domain\Search\Query\QueryBuilder;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\FacetRegistry;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\InvalidFacetPackageException;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\InvalidUrlDecoderException;
use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequest;
use ApacheSolrForTypo3\Solr\Event\Search\AfterSearchQueryHasBeenPreparedEvent;
use ApacheSolrForTypo3\Solr\Search;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use Psr\Log\LoggerAwareTrait;
use Solarium\QueryType\Select\Query\FilterQuery;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

class DefaultFacetSelection
{
    use LoggerAwareTrait;

    public function __construct(
        protected readonly QueryBuilder $queryBuilder,
        protected readonly FacetRegistry $facetRegistry
    ) {
    }

    public function __invoke(AfterSearchQueryHasBeenPreparedEvent $event): void
    {
        $configuration = $event->getTypoScriptConfiguration();
        $isFacetingEnabled = $configuration->getSearchFaceting();
        if ($isFacetingEnabled === false) {
            return;
        }

        $query = $this->modifyQuery(
            searchRequest: $event->getSearchRequest(),
            query: $event->getQuery(),
            search: $event->getSearch()
        );
        $event->setQuery($query);
    }

    protected function modifyQuery(
        SearchRequest $searchRequest,
        Query $query,
        Search $search
    ): Query {
        $activeFacetNames = $searchRequest->getActiveFacetNames();

        $defaultValuesOfFacets = $this->getDefaultFacetSelections($searchRequest);
        $defaultValuesOfFacets = array_filter($defaultValuesOfFacets, function ($facetName) use ($activeFacetNames) {
            return ! in_array($facetName, $activeFacetNames);
        }, ARRAY_FILTER_USE_KEY);

        foreach ($defaultValuesOfFacets as $facetName => $defaultSelection) {
            $defaultSelections = GeneralUtility::trimExplode(',', $defaultSelection);
            foreach ($defaultSelections as $selection) {
                $filterQuery = new FilterQuery([
                    'key' => 'type',
                    'query' => $this->getFacetQueryFilter(
                        $searchRequest->getContextTypoScriptConfiguration(),
                        $facetName,
                        (array) $selection
                    ),
                ]);
                $defaultFacetSelectionQuery = clone $query;
                $defaultFacetSelectionQuery->addFilterQuery($filterQuery);

                $result = $search->search($defaultFacetSelectionQuery);
                $rawCount = (int) ObjectAccess::getPropertyPath($result, 'response.numFound');
                $groupedCount = (int) ObjectAccess::getPropertyPath($result, 'facets.count');
                if ($rawCount > 0 || $groupedCount) {
                    $query->addFilterQuery($filterQuery);
                    $searchRequest->addFacetValue($facetName, $selection);

                    break;
                }
            }
        }

        return $query;
    }

    /**
     * Search all configured facets for default values
     */
    protected function getDefaultFacetSelections(SearchRequest $searchRequest): array
    {
        $facets = $searchRequest->getContextTypoScriptConfiguration()->getSearchFacetingFacets();
        if (empty($facets)) {
            return [];
        }

        $activeFacets = array_filter($facets, function ($facetConfiguration) {
            return (bool) $facetConfiguration['includeInAvailableFacets'];
        });
        $defaultFacetSelections = array_filter($activeFacets, function ($facetConfiguration) {
            return isset($facetConfiguration['defaultValue']);
        });

        $defaultFacetSelections = array_map(function ($facetName, $facetConfiguration) {
            return [rtrim($facetName, '.'), $facetConfiguration['defaultValue']];
        }, array_keys($defaultFacetSelections), array_values($defaultFacetSelections));

        return array_column($defaultFacetSelections, 1, 0);
    }

    /**
     * Build filter for facet selection
     */
    protected function getFacetQueryFilter(
        TypoScriptConfiguration $typoScriptConfiguration,
        string $facetName,
        array $filterValues
    ): string {
        $keepAllFacetsOnSelection = $typoScriptConfiguration->getSearchFacetingKeepAllFacetsOnSelection();
        $facetConfiguration = $typoScriptConfiguration->getSearchFacetingFacetByName($facetName);

        $tag = $this->getFilterTag($facetConfiguration, $keepAllFacetsOnSelection);
        $filterParts = $this->getFilterParts($facetConfiguration, $facetName, $filterValues);
        $operator = match ($facetConfiguration['operator'] ?? '') {
            'OR' => ' OR ',
            default => ' and ',
        };
        return $tag . '(' . implode($operator, $filterParts) . ')';
    }

    /**
     * Builds the tag part of the query depending on the keepAllOptionsOnSelection configuration or the global configuration
     * keepAllFacetsOnSelection.
     */
    protected function getFilterTag(array $facetConfiguration, bool $keepAllFacetsOnSelection): string
    {
        $tag = '';
        if (
            (int) ($facetConfiguration['keepAllOptionsOnSelection'] ?? 0) === 1
            || (int) ($facetConfiguration['addFieldAsTag'] ?? 0) === 1
            || $keepAllFacetsOnSelection
        ) {
            $tag = '{!tag=' . addslashes($facetConfiguration['field']) . '}';
        }

        return $tag;
    }

    /**
     * This method is used to build the filter parts of the query.
     *
     * @throws InvalidFacetPackageException
     * @throws InvalidUrlDecoderException
     */
    protected function getFilterParts(array $facetConfiguration, string $facetName, array $filterValues): array
    {
        $filterParts = [];

        $type = $facetConfiguration['type'] ?? 'options';
        $filterEncoder = $this->facetRegistry->getPackage($type)->getUrlDecoder();

        foreach ($filterValues as $filterValue) {
            $filterOptions = isset($facetConfiguration['type']) ? ($facetConfiguration[$facetConfiguration['type'] . '.'] ?? null) : null;
            if (empty($filterOptions)) {
                $filterOptions = [];
            }

            $filterValue = $filterEncoder->decode($filterValue, $filterOptions);
            if (($facetConfiguration['field'] ?? '') !== '' && $filterValue !== '') {
                $filterParts[] = $facetConfiguration['field'] . ':' . $filterValue;
            } else {
                $this->logger->warning(
                    'Invalid filter options found, skipping.',
                    ['facet' => $facetName, 'configuration' => $facetConfiguration]
                );
            }
        }

        return $filterParts;
    }
}
