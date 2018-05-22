<?php
namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Spellchecking\Suggestion;
use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class SearchResultSet extends \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet implements \JsonSerializable
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
        $resultsPerPage = $this->getResultsPerPage();
        $resultOffset = $this->getUsedSearch()->getResultOffset();
        $numberOfResults = $this->getUsedSearch()->getNumberOfResults();

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
                'q' => $this->usedQuery ? $this->usedQuery->getQueryStringContainer()->getKeywords() : '',
                'suggestion' => $this->getSuggestion(),
                'links' => [
                    'reset' => $this->getResetUrl(),
                    'next' => '',
                    'prev' => '',
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

        $result['search']['links']['next'] = $this->getNextUrl();
        $result['search']['links']['prev'] = $this->getPrevUrl();
        $result['result'] = [
            'q' => $this->usedQuery ? $this->usedQuery->getQueryStringContainer()->getKeywords() : '',
            'limit' => $this->getResultsPerPage(),
            'offset' => $this->usedSearch->getResultOffset(),
            'totalResults' => $this->usedSearch->getNumberOfResults(),
            'items' => $this->getSearchResults()->getArrayCopy(),
        ];

        if ($this->getPage() === 1) {
            $result['facets'] = $this->facets->getArrayCopy();
        }

        return $result;
    }

    /**
     * @return int
     */
    protected function getPage()
    {
        return $this->getUsedSearchRequest()->getPage() ?: 1;
    }


}
