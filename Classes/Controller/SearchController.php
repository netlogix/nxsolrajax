<?php

namespace Netlogix\Nxsolrajax\Controller;

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

class SearchController extends \ApacheSolrForTypo3\Solr\Controller\SearchController
{

    public function indexAction(): ResponseInterface
    {
        try {
            $searchResultSet = $this->getSearchResultSet();
        } catch (SolrCommunicationException $e) {
            return $this->handleSolrUnavailable();
        }

        if (strpos($this->request->getHeaderLine('Accept') ?? '', 'application/json') !== false) {
            $response = $this->jsonResponse(json_encode($searchResultSet));
        } else {
            if ($searchResultSet instanceof SearchResultSet) {
                $searchResultSet->forceAddFacetData();
            }
            $jsonData = json_encode($searchResultSet);
            $this->view->assign('resultSet', json_decode($jsonData, true));

            $response = $this->htmlResponse();
        }

        return $response;
    }

    public function resultsAction(): ResponseInterface
    {
        try {
            $searchResultSet = $this->getSearchResultSet();
            return $this->jsonResponse(json_encode($searchResultSet));
        } catch (SolrCommunicationException $e) {
            return $this->handleSolrUnavailable();
        }
    }

    /**
     * This method creates a suggest json response that can be used in a suggest layer.
     */
    public function suggestAction(): ResponseInterface
    {
        $queryString = $this->request->getArgument('q');
        $additionalFilters = $this->request->hasArgument('filters') ? $this->request->getArgument('filters') : [];

        try {
            $searchRequest = $this->getSearchRequest();
            $suggestService = GeneralUtility::makeInstance(
                SuggestService::class,
                $this->typoScriptFrontendController,
                $this->searchService,
                $this->typoScriptConfiguration
            );
            assert($suggestService instanceof SuggestService);

            $result = $suggestService->getSuggestions($searchRequest, $additionalFilters);

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
        } catch (SolrCommunicationException $e) {
            return $this->handleSolrUnavailable();
        }
    }

    public function solrNotAvailableAction(): ResponseInterface
    {
        return $this->responseFactory->createResponse(503, self::STATUS_503_MESSAGE)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody($this->streamFactory->createStream(json_encode(['status' => 503, 'message' => ''])));
    }

    protected function getSearchResultSet(): SolrSearchResultSet
    {
        $searchRequest = $this->getSearchRequest();
        $searchResultSet = $this->searchService->search($searchRequest);

        // we pass the search result set to the controller context, to have the possibility
        // to access it without passing it from partial to partial
        $this->view->getRenderingContext()->getVariableProvider()->add('searchResultSet', $searchResultSet);

        return $searchResultSet;
    }

    protected function getSearchRequest(): SearchRequest
    {
        $rawQuery = htmlspecialchars(mb_strtolower(trim($this->request->getArgument('q'))));
        $arguments = $this->request->getArguments();
        $pageId = $this->typoScriptFrontendController->getRequestedId();
        $languageId = $this->typoScriptFrontendController->getLanguage()->getLanguageId();
        return $this->getSearchRequestBuilder()->buildForSuggest(
            $arguments,
            $rawQuery,
            $pageId,
            $languageId
        );
    }

}
