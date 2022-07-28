<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\AbstractOptionFacetItem;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Spellchecking\Suggestion;
use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use JsonSerializable;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\Option;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping\Group;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping\GroupItem;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class SearchResultSet extends \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet implements
    JsonSerializable
{

    protected SearchUriBuilder $searchUriBuilder;

    protected UriBuilder $uriBuilder;

    /**
     * @var bool
     */
    protected bool $forceAddFacetData = false;

    /**
     * @inheritdoc
     */
    public function __construct()
    {
        parent::__construct();

        $this->searchUriBuilder = GeneralUtility::makeInstance(ObjectManager::class)->get(SearchUriBuilder::class);
        $this->uriBuilder = GeneralUtility::makeInstance(ObjectManager::class)->get(UriBuilder::class);
    }

    public function jsonSerialize(): array
    {
        $result = [
            'search' => [
                'q' => $this->usedQuery ? $this->usedQuery->getQuery() : '',
                'suggestion' => $this->getSuggestion(),
                'links' => [
                    'reset' => $this->getResetUrl(),
                    'first' => '',
                    'prev' => '',
                    'next' => '',
                    'last' => '',
                    'search' => $this->getSearchUrl(),
                    'suggest' => $this->getSuggestUrl(),
                    'suggestion' => $this->getSuggestionUrl()
                ]
            ],
            'facets' => [],
            'result' => [],
        ];
        if ($this->response === null) {
            return $result;
        }

        $highlightedContent = $this->getUsedSearch()->getHighlightedContent();

        /** @var SearchResult $document */
        foreach ($this->getSearchResults() as $document) {
            if (!empty($highlightedContent->{$document->getId()}->content[0])) {
                $content = implode(' [...] ', $highlightedContent->{$document->getId()}->content);
                $document->setField('highlightedContent', $content);
            }
        }

        $result['search']['links']['first'] = $this->getFirstUrl();
        $result['search']['links']['prev'] = $this->getPrevUrl();
        $result['search']['links']['next'] = $this->getNextUrl();
        $result['search']['links']['last'] = $this->getLastUrl();

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

        $result['result'] = [
            'q' => $this->usedQuery ? $this->usedQuery->getQuery() : '',
            'limit' => $this->getUsedResultsPerPage(),
            'offset' => $this->usedSearch->getResultOffset(),
            'totalResults' => $this->getAllResultCount(),
            'items' => [],
            'groups' => [],
        ];

        if ($result['facetsDataValid'] = $this->shouldAddFacetData()) {
            $result['facets'] = $this->getFacets()->getAvailable()->getArrayCopy();
        }


        $result['sortings'] = $this->getSortings()->getArrayCopy();
        if (!$result['sortings']) {
            unset($result['sortings']);
        }

        if ($this->isGroupingEnabled()) {
            $groups = $this->getSearchResults()->getGroups()->getArrayCopy();
            foreach ($groups as $group) {
                assert($group instanceof Group);
                foreach ($group->getGroupItems() as $groupItem) {
                    if ($facet = $this->getFacets()->getByName($group->getGroupName())->getByPosition(0)) {
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
        } else {
            $result['result']['items'] = $this->getSearchResults()->getArrayCopy();
        }

        return $result;
    }

    public function getSuggestion(): string
    {
        if (!$this->getHasSpellCheckingSuggestions()) {
            return '';
        }
        return current($this->spellCheckingSuggestions)->getSuggestion();
    }

    public function getResetUrl(): string
    {
        return $this->uriBuilder->reset()->build();
    }

    public function getSearchUrl(): string
    {
        $previousRequest = $this->getUsedSearchRequest();
        return $this->searchUriBuilder->getNewSearchUri($previousRequest, '{query}');
    }

    public function getSuggestUrl(): string
    {
        return $this->uriBuilder->reset()->setTargetPageType('1471261352')->build();
    }

    public function getSuggestionUrl(): string
    {
        if (!$this->getHasSpellCheckingSuggestions()) {
            return '';
        }
        /** @var Suggestion $suggestion */
        $suggestion = current($this->spellCheckingSuggestions)->getSuggestion();
        $previousRequest = $this->getUsedSearchRequest();
        return $this->searchUriBuilder->getNewSearchUri($previousRequest, $suggestion);
    }

    public function getFirstUrl(): string
    {
        $uri = '';
        $previousRequest = $this->getUsedSearchRequest();
        $page = $this->getPage();
        if ($page > 2) {
            $uri = $this->searchUriBuilder->getResultPageUri($previousRequest, 1);
        }
        return $uri;
    }

    protected function getPage(): int
    {
        return $this->getUsedSearchRequest()->getPage() ?: 1;
    }

    public function getPrevUrl(): string
    {
        $uri = '';
        $previousRequest = $this->getUsedSearchRequest();
        $page = $this->getPage();
        if ($page > 1) {
            $uri = $this->searchUriBuilder->getResultPageUri($previousRequest, $page - 1);
        }
        return $uri;
    }

    public function getNextUrl(): string
    {
        $uri = '';
        $previousRequest = $this->getUsedSearchRequest();
        $page = $this->getPage();
        $resultsPerPage = $this->getUsedResultsPerPage();
        $resultOffset = $this->getUsedSearch()->getResultOffset();
        $numberOfResults = $this->getAllResultCount();

        if ($numberOfResults - $resultsPerPage > $resultOffset) {
            $uri = $this->searchUriBuilder->getResultPageUri($previousRequest, $page + 1);
        }
        return $uri;
    }

    public function getLastUrl(): string
    {
        $uri = '';
        $previousRequest = $this->getUsedSearchRequest();
        $resultsPerPage = $this->getUsedResultsPerPage();
        $resultOffset = $this->getUsedSearch()->getResultOffset();
        $numberOfResults = $this->getAllResultCount();

        if ($numberOfResults - (2 * $resultsPerPage) > $resultOffset) {
            $uri = $this->searchUriBuilder->getResultPageUri(
                $previousRequest,
                (int)ceil($numberOfResults / $resultsPerPage)
            );
        }
        return $uri;
    }

    protected function shouldAddFacetData(): bool
    {
        return $this->forceAddFacetData || $this->getPage() === 1;
    }

    protected function isGroupingEnabled(): bool
    {
        return $this->searchResults->getHasGroups();
    }

    public function forceAddFacetData(bool $forceAddFacetData = true)
    {
        $this->forceAddFacetData = $forceAddFacetData;
    }

}
