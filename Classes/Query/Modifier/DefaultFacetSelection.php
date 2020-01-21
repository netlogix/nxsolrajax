<?php

namespace Netlogix\Nxsolrajax\Query\Modifier;

use ApacheSolrForTypo3\Solr\Domain\Search\Query\Query;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\FacetRegistry;
use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequestAware;
use ApacheSolrForTypo3\Solr\Query\Modifier\Faceting;
use ApacheSolrForTypo3\Solr\Query\Modifier\Modifier;
use ApacheSolrForTypo3\Solr\Search;
use ApacheSolrForTypo3\Solr\Search\SearchAware;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use ApacheSolrForTypo3\Solr\Util;
use Exception;
use Solarium\QueryType\Select\Query\FilterQuery;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

class DefaultFacetSelection extends Faceting implements Modifier, SearchRequestAware, SearchAware
{
    /**
     * @var TypoScriptConfiguration
     */
    protected $configuration;

    /**
     * @var Search
     */
    protected $search;

    /**
     * @inheritdoc
     */
    public function __construct(FacetRegistry $facetRegistry = null)
    {
        parent::__construct($facetRegistry);
        $this->configuration = Util::getSolrConfiguration();
    }

    /**
     * @inheritdoc
     */
    public function setSearch(Search $search)
    {
        $this->search = $search;
    }

    /**
     * TODO: Update to
     * @param Query $query
     * @return Query
     * @throws Exception
     */
    public function modifyQuery(Query $query)
    {
        $activeFacetNames = $this->searchRequest->getActiveFacetNames();

        $defaultValuesOfFacets = $this->getDefaultFacetSelections();
        $defaultValuesOfFacets = array_filter($defaultValuesOfFacets, function ($facetName) use ($activeFacetNames) {
            return !in_array($facetName, $activeFacetNames);
        }, ARRAY_FILTER_USE_KEY);

        foreach ($defaultValuesOfFacets as $facetName => $defaultSelection) {

            $defaultSelections = GeneralUtility::trimExplode(',', $defaultSelection);
            foreach ($defaultSelections as $selection) {

                $defaultFacetSelectionQuery = clone $query;
                $defaultFacetSelectionQuery->setFilters(clone $defaultFacetSelectionQuery->getFilters());
                $defaultFacetSelectionQuery->getFilters()->add($this->getFacetQueryFilter($facetName, (array)$selection));

                $result = $this->search->search($defaultFacetSelectionQuery);
                $rawCount = (int)ObjectAccess::getPropertyPath($result, 'parsedData.response.numFound');
                $groupedCount = (int)ObjectAccess::getPropertyPath($result, 'parsedData.facets.count');
                if ($rawCount > 0 || $groupedCount) {

                    $query->setFilters($defaultFacetSelectionQuery->getFilters());
                    $this->searchRequest->addFacetValue($facetName, $selection);

                    break;
                }
            }

        }

        return $query;
    }

    /**
     * Search all configured facets for default values
     *
     * @return array Array with all facets and default values
     */
    protected function getDefaultFacetSelections()
    {
        $facets = $this->configuration->getSearchFacetingFacets();
        if (empty($facets)) {
            return [];
        }

        $activeFacets = array_filter($facets, function ($facetConfiguration) {
            return (bool)$facetConfiguration['includeInAvailableFacets'];
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
     *
     * @param string $facetName Name of the facet
     * @param string $filterValues Value to filter for
     * @return string The filter ready to add to the query
     */
    protected function getFacetQueryFilter(string $facetName, array $filterValues)
    {
        $typoScriptConfiguration = $this->searchRequest->getContextTypoScriptConfiguration();
        $allFacets = $typoScriptConfiguration->getSearchFacetingFacets();
        $keepAllFacetsOnSelection = $typoScriptConfiguration->getSearchFacetingKeepAllFacetsOnSelection();

        $facetConfiguration = $allFacets[$facetName . '.'];
        $tag = $this->getFilterTag($facetConfiguration, $keepAllFacetsOnSelection);
        $filterParts = $this->getFilterParts($facetConfiguration, $facetName, $filterValues);
        $operator = ($facetConfiguration['operator'] === 'OR') ? ' OR ' : ' AND ';
        return $tag . '(' . implode($operator, $filterParts) . ')';
    }
}
