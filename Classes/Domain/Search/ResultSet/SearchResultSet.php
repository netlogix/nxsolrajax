<?php
namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet;

use ApacheSolrForTypo3\Solrfluid\Domain\Search\Uri\SearchUriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class SearchResultSet extends \ApacheSolrForTypo3\Solrfluid\Domain\Search\ResultSet\SearchResultSet implements \JsonSerializable
{

    /**
     * @var SearchUriBuilder
     */
    protected $searchUriBuilder;

    /**
     * @inheritdoc
     */
    public function __construct()
    {
        parent::__construct();

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->searchUriBuilder = $objectManager->get(SearchUriBuilder::class);
    }

    /**
     * @return string
     */
    public function getResetUrl()
    {
        $previousRequest = $this->getUsedSearchRequest();
        return $this->searchUriBuilder->getRemoveAllFacetsUri($previousRequest);
    }

    /**
     * @return string
     */
    public function getNextUrl()
    {
        $uri = '';
        $previousRequest = $this->getUsedSearchRequest();
        $page = $this->getPage();
        $resultsPerPage = $this->getUsedQuery()->getResultsPerPage();
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
        return $this->searchUriBuilder->getNewSearchUri($previousRequest, 'QUERY_STRING');
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $highlightedContent = $this->getUsedSearch()->getHighlightedContent();

        /** @var SearchResult $document */
        foreach ($this->getUsedSearch()->getResultDocumentsEscaped() as $document) {
            if (!empty($highlightedContent->{$document->getId()}->content[0])) {
                $content = implode(' [...] ', $highlightedContent->{$document->getId()}->content);
                $document->setField('highlightedContent', $content);
            }
        }

        return [
            'search' => [
                'q' => $this->usedQuery->getKeywords(),
                'suggestion' => $this->usedSearch->getSpellcheckingSuggestions(),
                'links' => [
                    'reset' => $this->getResetUrl(),
                    'next' => $this->getNextUrl(),
                    'prev' => $this->getPrevUrl(),
                    'search' => $this->getSearchUrl(),
                ]
            ],
            'facets' => $this->facets->getArrayCopy(),
            'result' => [
                'q' => $this->usedQuery->getKeywords(),
                'limit' => $this->usedQuery->getResultsPerPage(),
                'offset' => $this->usedSearch->getResultOffset(),
                'totalResults' => $this->usedSearch->getNumberOfResults(),
                'items' => $this->getUsedSearch()->getResultDocumentsEscaped()
            ]
        ];
    }

    /**
     * @return int
     */
    protected function getPage()
    {
        return $this->getUsedSearchRequest()->getPage() + 1;
    }
}