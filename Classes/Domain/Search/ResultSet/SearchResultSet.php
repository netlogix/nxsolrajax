<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\AbstractOptionFacetItem;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Spellchecking\Suggestion;
use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use ApacheSolrForTypo3\Solrfluidgrouping\Query\Modifier\Grouping;
use JsonSerializable;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\Option;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping\Group;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping\GroupItem;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class SearchResultSet extends \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet implements JsonSerializable
{

    /**
     * @var SearchUriBuilder
     */
    protected $searchUriBuilder;

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * @var bool
     */
    protected $forceAddFacetData = false;

    /**
     * @inheritdoc
     */
    public function __construct()
    {
        parent::__construct();

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->searchUriBuilder = $objectManager->get(SearchUriBuilder::class);
        $this->uriBuilder = $objectManager->get(UriBuilder::class);
    }

    /**
     * @return string
     */
    public function getResetUrl()
    {
        return $this->uriBuilder->reset()->build();
    }

    /**
     * @return string
     */
    public function getNextUrl()
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

    /**
     * @return string
     */
    public function getPrevUrl()
    {
        $uri = '';
        $previousRequest = $this->getUsedSearchRequest();
        $page = $this->getPage();
        if ($page > 1) {
            $uri = $this->searchUriBuilder->getResultPageUri($previousRequest, $page - 1);
        }
        return $uri;
    }

    /**
     * @return string
     */
    public function getFirstUrl()
    {
        $uri = '';
        $previousRequest = $this->getUsedSearchRequest();
        $page = $this->getPage();
        if ($page > 2) {
            $uri = $this->searchUriBuilder->getResultPageUri($previousRequest, 1);
        }
        return $uri;
    }

    /**
     * @return string
     */
    public function getLastUrl()
    {
        $uri = '';
        $previousRequest = $this->getUsedSearchRequest();
        $resultsPerPage = $this->getUsedResultsPerPage();
        $resultOffset = $this->getUsedSearch()->getResultOffset();
        $numberOfResults = $this->getAllResultCount();

        if ($numberOfResults - (2 * $resultsPerPage) > $resultOffset) {
            $uri = $this->searchUriBuilder->getResultPageUri($previousRequest, ceil($numberOfResults / $resultsPerPage));
        }
        return $uri;
    }

    /**
     * @return string
     */
    public function getSearchUrl()
    {
        $previousRequest = $this->getUsedSearchRequest();
        return $this->searchUriBuilder->getNewSearchUri($previousRequest, '{query}');
    }

    /**
     * @return string
     */
    public function getSuggestUrl()
    {
        return $this->uriBuilder->reset()->setTargetPageType('1471261352')->build();
    }

    /**
     * @return string
     */
    public function getSuggestionUrl()
    {
        if (!$this->getHasSpellCheckingSuggestions()) {
            return '';
        }
        /** @var Suggestion $suggestion */
        $suggestion = current($this->spellCheckingSuggestions)->getSuggestion();
        $previousRequest = $this->getUsedSearchRequest();
        return $this->searchUriBuilder->getNewSearchUri($previousRequest, $suggestion);
    }

    /**
     * @return string
     */
    public function getSuggestion()
    {
        if (!$this->getHasSpellCheckingSuggestions()) {
            return '';
        }
        return current($this->spellCheckingSuggestions)->getSuggestion();
    }

    /**
     * @return array
     */
    public function jsonSerialize()
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
            $query = GeneralUtility::explodeUrl2Array($uri->getQuery());
            if (isset($query['tx_solr[page]']) && (string)$query['tx_solr[page]'] === '1') {
                unset($query['tx_solr[page]']);
            }
            return (string)$uri->withQuery(GeneralUtility::implodeArrayForUrl('', $query));
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
                foreach ($group->getGroupItems() as &$groupItem) {
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

    /**
     * @param bool $forceAddFacetData
     */
    public function forceAddFacetData($forceAddFacetData = true)
    {
        $this->forceAddFacetData = $forceAddFacetData;
    }

    /**
     * @return int
     */
    protected function getPage()
    {
        return $this->getUsedSearchRequest()->getPage() ?: 1;
    }

    /**
     * @return bool
     */
    protected function isGroupingEnabled()
    {
        return $this->searchResults->getHasGroups() && $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['modifySearchQuery']['fluid_grouping'] = Grouping::class;
    }

    /**
     * @return bool
     */
    protected function shouldAddFacetData()
    {
        return $this->forceAddFacetData || $this->getPage() === 1;
    }

}
