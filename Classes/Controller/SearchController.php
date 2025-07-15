<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Controller;

use Override;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet as SolrSearchResultSet;
use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequest;
use ApacheSolrForTypo3\Solr\Domain\Search\Suggest\SuggestService;
use ApacheSolrForTypo3\Solr\System\Solr\SolrCommunicationException;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SearchResultSet;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SuggestResultSet;
use Netlogix\Nxsolrajax\Event\Search\AfterGetSuggestionsEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;

class SearchController extends \ApacheSolrForTypo3\Solr\Controller\SearchController
{
    public function indexAction(): ResponseInterface
    {
        try {
            $searchResultSet = $this->getSearchResultSet();
        } catch (SolrCommunicationException) {
            return $this->handleSolrUnavailable();
        }
        if (str_contains($this->request->getHeaderLine('Accept') ?? '', 'application/json')) {
            return $this->jsonResponse(json_encode($searchResultSet));
        }
        if ($searchResultSet instanceof SearchResultSet) {
            $searchResultSet->forceAddFacetData();
        }

        $jsonData = json_encode($searchResultSet);
        $this->view->assign('resultSet', json_decode($jsonData, true));

        return $this->htmlResponse();
    }

    #[Override]
    public function resultsAction(): ResponseInterface
    {
        try {
            $searchResultSet = $this->getSearchResultSet();
            return $this->jsonResponse(json_encode($searchResultSet));
        } catch (SolrCommunicationException) {
            return $this->handleSolrUnavailable();
        }
    }

    /**
     * This method creates a suggest json response that can be used in a suggest layer.
     */
    public function suggestAction(): ResponseInterface
    {
        try {
            $queryString = $this->request->getArgument('q');
            $additionalFilters = $this->request->hasArgument('filters') ? $this->request->getArgument('filters') : [];
        } catch (NoSuchArgumentException) {
            return $this->jsonResponse(json_encode([]));
        }

        try {
            $searchRequest = $this->getSuggestRequest();
            $suggestService = GeneralUtility::makeInstance(
                SuggestService::class,
                $this->typoScriptConfiguration,
                $this->searchService
            );
            assert($suggestService instanceof SuggestService);

            $result = $suggestService->getSuggestions($this->request, $searchRequest, $additionalFilters);

            $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)
                ->dispatch(
                    new AfterGetSuggestionsEvent(
                        $queryString,
                        $result['suggestions'] ?? [],
                        $this->typoScriptConfiguration
                    )
                );
            assert($event instanceof AfterGetSuggestionsEvent);
            $result['suggestions'] = $event->getSuggestions();

            $suggestResult = GeneralUtility::makeInstance(
                SuggestResultSet::class,
                $result['suggestions'] ?? [],
                $result['suggestion'] ?? ''
            );

            return $this->jsonResponse(json_encode($suggestResult));
        } catch (SolrCommunicationException) {
            return $this->handleSolrUnavailable();
        }
    }

    #[Override]
    public function solrNotAvailableAction(): ResponseInterface
    {
        return str_contains($this->request->getHeaderLine('Accept') ?? '', 'application/json')
            ? $this->jsonResponse(json_encode(['status' => 503, 'message' => self::STATUS_503_MESSAGE]))
            : $this->htmlResponse(self::STATUS_503_MESSAGE)
                ->withStatus(503, self::STATUS_503_MESSAGE);
    }

    protected function getSearchResultSet(): SolrSearchResultSet
    {
        $searchRequest = $this->getSearchRequest();
        $searchResultSet = $this->searchService->search($searchRequest);

        // we pass the search result set to the controller context, to have the possibility
        // to access it without passing it from partial to partial
        $this->view->getRenderingContext()->getVariableProvider()->add('searchResultSet', $searchResultSet);

        $searchResultSet->setRequest($this->request);
        return $searchResultSet;
    }

    protected function getSearchRequest(): SearchRequest
    {
        $arguments = $this->request->getArguments();
        $pageId = $this->request->getAttribute('routing')->getPageId();
        $languageId = $this->request->getAttribute('language')->getLanguageId();
        return $this->getSearchRequestBuilder()->buildForSearch(
            $arguments,
            $pageId,
            $languageId
        );
    }

    protected function getSuggestRequest(): SearchRequest
    {
        if ($this->request->hasArgument('q')) {
            $rawQuery = htmlspecialchars(mb_strtolower(trim((string) $this->request->getArgument('q'))));
        } else {
            $rawQuery = '';
        }

        $arguments = $this->request->getArguments();
        $pageId = $this->request->getAttribute('routing')->getPageId();
        $languageId = $this->request->getAttribute('language')->getLanguageId();
        return $this->getSearchRequestBuilder()->buildForSuggest(
            $arguments,
            $rawQuery,
            $pageId,
            $languageId
        );
    }

}
