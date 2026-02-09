<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Service;

use ApacheSolrForTypo3\Solr\Domain\Search\Query\Query;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\AbstractOptionFacetItem;
use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use ApacheSolrForTypo3\Solr\System\Solr\ResponseAdapter;
use Exception;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\Option;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping\Group;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping\GroupItem;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SearchResult;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SearchResultSet;
use stdClass;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\SingletonInterface;

class SearchResultSetConverterService implements SingletonInterface
{
    protected SearchUriBuilder $searchUriBuilder;

    public function setSearchUriBuilder(SearchUriBuilder $searchUriBuilder): self
    {
        $this->searchUriBuilder = $searchUriBuilder;
        return $this;
    }

    public function toArray(SearchResultSet $searchResultSet): array
    {
        $result = $this->generateBasicSearchResultData($searchResultSet);

        if (!$searchResultSet->getResponse() instanceof ResponseAdapter) {
            return $result;
        }

        $this->highlightSearchResults($searchResultSet);

        $result = $this->addLinksToSearchResultData($result, $searchResultSet);

        $result = $this->addQueryToSearchResultData($result, $searchResultSet);

        $result = $this->addFacetsToSearchResultData($result, $searchResultSet);

        $result = $this->addSortingToSearchResultData($result, $searchResultSet);

        return $this->groupSearchResultData($result, $searchResultSet);
    }

    public function toJSON(SearchResultSet $searchResultSet): string
    {
        $json = json_encode($this->toArray($searchResultSet));

        if (json_last_error() != JSON_ERROR_NONE) {
            // todo add custom exception
            throw new Exception(json_last_error_msg(), 1659079186);
        }

        return $json;
    }

    protected function generateBasicSearchResultData(SearchResultSet $searchResultSet): array
    {
        return [
            'search' => [
                'q' => $searchResultSet->getUsedQuery() instanceof Query ? $searchResultSet->getUsedQuery()->getQuery() : '',
                'suggestion' => $searchResultSet->getSuggestion(),
                'links' => [
                    'reset' => $searchResultSet->getResetUrl(),
                    'first' => '',
                    'prev' => '',
                    'next' => '',
                    'last' => '',
                    'search' => $searchResultSet->getSearchUrl(),
                    'suggest' => $searchResultSet->getSuggestUrl(),
                    'suggestion' => $searchResultSet->getSuggestionUrl()
                ]
            ],
            'facets' => [],
            'result' => [],
        ];
    }

    protected function highlightSearchResults(SearchResultSet $searchResultSet): void
    {
        $highlightedContent = $searchResultSet->getUsedSearch()->getHighlightedContent();

        if (!$highlightedContent instanceof stdClass) {
            return;
        }

        /** @var SearchResult $document */
        foreach ($searchResultSet->getSearchResults() as $document) {
            if (!empty($highlightedContent->{$document->getId()}->content[0])) {
                $content = implode(' [...] ', $highlightedContent->{$document->getId()}->content);
                $document->setField('highlightedContent', $content);
            }
        }
    }

    protected function addLinksToSearchResultData(array $result, SearchResultSet $searchResultSet): array
    {
        $result['search']['links']['first'] = $searchResultSet->getFirstUrl();
        $result['search']['links']['prev'] = $searchResultSet->getPrevUrl();
        $result['search']['links']['next'] = $searchResultSet->getNextUrl();
        $result['search']['links']['last'] = $searchResultSet->getLastUrl();

        $result['search']['links'] = array_map(function ($uri): array|int|float|false|null|string {
            if (!$uri) {
                return $uri;
            }

            $uri = new Uri($uri);
            $query = $uri->getQuery();
            $query = explode('&', $query);
            $query = array_filter($query, fn ($query): bool => $query !== 'tx_solr[page]=1');
            $queryString = trim(implode('&', $query), '&');
            return (string) $uri->withQuery($queryString);
        }, $result['search']['links']);
        return $result;
    }

    protected function addQueryToSearchResultData(array $result, SearchResultSet $searchResultSet): array
    {
        $result['result'] = [
            'q' => $searchResultSet->getUsedQuery() instanceof Query ? $searchResultSet->getUsedQuery()->getQuery() : '',
            'limit' => $searchResultSet->getUsedResultsPerPage(),
            'offset' => $searchResultSet->getUsedSearch()->getResultOffset(),
            'totalResults' => $searchResultSet->getAllResultCount(),
            'items' => [],
            'groups' => [],
        ];
        return $result;
    }

    protected function addFacetsToSearchResultData(array $result, SearchResultSet $searchResultSet): array
    {
        if ($result['facetsDataValid'] = $searchResultSet->shouldAddFacetData()) {
            $facets = $searchResultSet->getFacets()->getAvailable()->getArrayCopy();
            foreach ($facets as $facet) {
                if (!method_exists($facet, 'setSearchUriBuilder')) {
                    continue;
                }
                $facet->setSearchUriBuilder($this->searchUriBuilder);
            }
            $result['facets'] = $facets;
        }

        return $result;
    }

    protected function addSortingToSearchResultData(array $result, SearchResultSet $searchResultSet): array
    {
        $sortings = $searchResultSet->getSortings()->getArrayCopy();
        foreach ($sortings as $sorting) {
            if (!method_exists($sorting, 'setSearchUriBuilder')) {
                continue;
            }
            $sorting->setSearchUriBuilder($this->searchUriBuilder);
        }
        $result['sortings'] = $sortings;
        if ($result['sortings'] === []) {
            unset($result['sortings']);
        }

        return $result;
    }

    /**
     * @return mixed[]
     */
    protected function groupSearchResultData(array $result, SearchResultSet $searchResultSet): array
    {
        if (!$searchResultSet->isGroupingEnabled()) {
            $result['result']['items'] = $searchResultSet->getSearchResults()->getArrayCopy();
            return $result;
        }

        $groups = $searchResultSet->getSearchResults()->getGroups()->getArrayCopy();
        foreach ($groups as $group) {
            assert($group instanceof Group);
            $group->setSearchUriBuilder($this->searchUriBuilder);
            foreach ($group->getGroupItems() as $groupItem) {
                if (($facet = $searchResultSet->getFacets()->getByName($group->getGroupName())->getByPosition(
                        0
                    )) !== null) {
                    assert($groupItem instanceof GroupItem);
                    $option = $facet->getOptions()->getByValue($groupItem->getGroupValue());
                    $option->setSearchUriBuilder($this->searchUriBuilder);
                    assert($option instanceof AbstractOptionFacetItem);
                    if ($option instanceof Option) {
                        $groupItem->setGroupUrl($option->getUrl());
                        $groupItem->setGroupLabel($option->getLabel());
                    }
                }
            }
        }

        $result['result']['groups'] = $groups;
        return $result;
    }
}
