<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Service;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\AbstractOptionFacetItem;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\Option;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping\Group;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping\GroupItem;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SearchResult;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SearchResultSet;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\SingletonInterface;

class SearchResultSetConverterService implements SingletonInterface
{

    public function toArray(SearchResultSet $searchResultSet): array
    {
        $result = $this->generateBasicSearchResultData($searchResultSet);

        if ($searchResultSet->getResponse() === null) {
            return $result;
        }

        $this->highlightSearchResults($searchResultSet);

        $result = $this->addLinksToSearchResultData($result, $searchResultSet);

        $result = $this->addQueryToSearchResultData($result, $searchResultSet);

        $result = $this->addFacetsToSearchResultData($result, $searchResultSet);

        $result = $this->addSortingToSearchResultData($result, $searchResultSet);

        $result = $this->groupSearchResultData($result, $searchResultSet);

        return $result;
    }

    public function toJSON(SearchResultSet $searchResultSet): string
    {
        $json = json_encode($this->toArray($searchResultSet));

        if (json_last_error() != JSON_ERROR_NONE) {
            // todo add custom exception
            throw new \Exception(json_last_error_msg(), 1659079186);
        }

        return $json;
    }


    /**
     * @return array
     */
    protected function generateBasicSearchResultData(SearchResultSet $searchResultSet): array
    {
        $result = [
            'search' => [
                'q' => $searchResultSet->getUsedQuery() ? $searchResultSet->getUsedQuery()->getQuery() : '',
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
        return $result;
    }

    /**
     * @return void
     */
    protected function highlightSearchResults(SearchResultSet $searchResultSet): void
    {
        $highlightedContent = $searchResultSet->getUsedSearch()->getHighlightedContent();

        if (!$highlightedContent) {
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

    /**
     * @param array $result
     * @return array
     */
    protected function addLinksToSearchResultData(array $result, SearchResultSet $searchResultSet): array
    {
        $result['search']['links']['first'] = $searchResultSet->getFirstUrl();
        $result['search']['links']['prev'] = $searchResultSet->getPrevUrl();
        $result['search']['links']['next'] = $searchResultSet->getNextUrl();
        $result['search']['links']['last'] = $searchResultSet->getLastUrl();

        $result['search']['links'] = array_map(function ($uri) {
            if (!$uri) {
                return $uri;
            }
            $uri = new Uri($uri);
            $query = $uri->getQuery();
            $query = explode('&', $query);
            $query = array_filter($query, function ($query) {
                return $query !== 'tx_solr[page]=1';
            });
            $queryString = trim(implode('&', $query), '&');
            return (string)$uri->withQuery($queryString);
        }, $result['search']['links']);
        return $result;
    }


    /**
     * @param array $result
     * @return array
     */
    protected function addQueryToSearchResultData(array $result, SearchResultSet $searchResultSet): array
    {
        $result['result'] = [
            'q' => $searchResultSet->getUsedQuery() ? $searchResultSet->getUsedQuery()->getQuery() : '',
            'limit' => $searchResultSet->getUsedResultsPerPage(),
            'offset' => $searchResultSet->getUsedSearch()->getResultOffset(),
            'totalResults' => $searchResultSet->getAllResultCount(),
            'items' => [],
            'groups' => [],
        ];
        return $result;
    }


    /**
     * @param array $result
     * @return array
     */
    protected function addFacetsToSearchResultData(array $result, SearchResultSet $searchResultSet): array
    {
        if ($result['facetsDataValid'] = $searchResultSet->shouldAddFacetData()) {
            $result['facets'] = $searchResultSet->getFacets()->getAvailable()->getArrayCopy();
        }
        return $result;
    }

    /**
     * @param array $result
     * @return array
     */
    protected function addSortingToSearchResultData(array $result, SearchResultSet $searchResultSet): array
    {
        $result['sortings'] = $searchResultSet->getSortings()->getArrayCopy();
        if (!$result['sortings']) {
            unset($result['sortings']);
        }
        return $result;
    }


    /**
     * @param array $result
     * @return array|void
     */
    protected function groupSearchResultData(array $result, SearchResultSet $searchResultSet)
    {
        if (!$searchResultSet->isGroupingEnabled()) {
            $result['result']['items'] = $searchResultSet->getSearchResults()->getArrayCopy();
            return $result;
        }

        $groups = $searchResultSet->getSearchResults()->getGroups()->getArrayCopy();
        foreach ($groups as $group) {
            assert($group instanceof Group);
            foreach ($group->getGroupItems() as $groupItem) {
                if ($facet = $searchResultSet->getFacets()->getByName($group->getGroupName())->getByPosition(0)) {
                    assert($groupItem instanceof GroupItem);
                    $option = $facet->getOptions()->getByValue($groupItem->getGroupValue());
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