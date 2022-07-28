<?php

namespace Netlogix\Nxsolrajax\Controller;

use ApacheSolrForTypo3\Solr\Domain\Search\Suggest\SuggestService;
use ApacheSolrForTypo3\Solr\System\Solr\SolrUnavailableException;
use ApacheSolrForTypo3\Solr\Util;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SearchResultSet;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SuggestResultSet;
use Netlogix\Nxsolrajax\Event\Search\AfterGetSuggestionsEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class SearchController extends \ApacheSolrForTypo3\Solr\Controller\SearchController
{

    public function indexAction(): ResponseInterface
    {
        try {
            $searchResultSet = $this->getSearchResultSet();
        } catch (SolrUnavailableException $e) {
            return $this->handleSolrUnavailable();
        }

        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
            $response = $this->jsonResponse(json_encode($searchResultSet));
        } else {
            if ($searchResultSet instanceof SearchResultSet) {
                $searchResultSet->forceAddFacetData();
            }
            $jsonData = json_encode($searchResultSet);
            $this->view->assign('resultSet', json_decode($jsonData, true));
            $this->view->assign('resultSetJson', $jsonData);

            $response = $this->htmlResponse();
        }

        return $this->applyHttpHeadersToResponse($response);
    }

    protected function getSearchResultSet(): \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet
    {
        $arguments = $this->request->getArguments();
        $pageId = $this->typoScriptFrontendController->getRequestedId();
        $languageId = Util::getLanguageUid();
        $searchRequest = $this->getSearchRequestBuilder()->buildForSearch($arguments, $pageId, $languageId);

        $searchResultSet = $this->searchService->search($searchRequest);

        // we pass the search result set to the controller context, to have the possibility
        // to access it without passing it from partial to partial
        $this->controllerContext->setSearchResultSet($searchResultSet);

        return $searchResultSet;
    }

    protected function applyHttpHeadersToResponse(ResponseInterface $response): ResponseInterface
    {
        // todo all headers should be applied automatically
        return $response;

        // Allow to cache the uncached plugin and manuel send cache Headers
        $tsfe = $this->getTypoScriptFrontendController();

        // Backup TSFE state
        $noCache = $tsfe->no_cache;
        $intincscript = $tsfe->config['INTincScript'];

        $tsfe->no_cache = false;
        $tsfe->config['config']['sendCacheHeaders'] = true;
        unset($tsfe->config['INTincScript']);

        foreach ($tsfe->applyHttpHeadersToResponse(new Response())->getHeaders() as $name => $values) {
            $response->withHeader($name, implode(', ', $values));
        }

        // Restore TSFE
        $tsfe->no_cache = $noCache;
        $tsfe->config['INTincScript'] = $intincscript;
        $tsfe->config['config']['sendCacheHeaders'] = false;

        return $response;
    }

    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

    public function resultsAction(): ResponseInterface
    {
        try {
            $searchResultSet = $this->getSearchResultSet();
            $response = $this->jsonResponse(json_encode($searchResultSet));

            return $this->applyHttpHeadersToResponse($response);
        } catch (SolrUnavailableException $e) {
            return $this->handleSolrUnavailable();
        }
    }

    /**
     * This method creates a suggest json response that can be used in a suggest layer.
     *
     * @return ResponseInterface
     * @throws NoSuchArgumentException
     */
    public function suggestAction(): ResponseInterface
    {
        $queryString = $this->request->getArgument('q');
        $rawQuery = htmlspecialchars(mb_strtolower(trim($queryString)));

        $additionalFilters = $this->request->hasArgument('filters') ? $this->request->getArgument('filters') : [];

        try {
            /** @var SuggestService $suggestService */
            $suggestService = GeneralUtility::makeInstance(
                SuggestService::class,
                $this->typoScriptFrontendController,
                $this->searchService,
                $this->typoScriptConfiguration
            );

            $pageId = $this->typoScriptFrontendController->getRequestedId();
            $languageId = Util::getLanguageUid();
            $arguments = $this->request->getArguments();
            $searchRequest = $this->getSearchRequestBuilder()->buildForSuggest(
                $arguments,
                $rawQuery,
                $pageId,
                $languageId
            );
            $result = $suggestService->getSuggestions($searchRequest, $additionalFilters);


            $event = new AfterGetSuggestionsEvent(
                $queryString,
                $result['suggestions'] ?? [],
                $this->typoScriptConfiguration
            );
            /** @var AfterGetSuggestionsEvent $event */
            $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch($event);
            $result['suggestions'] = $event->getSuggestions();


            $suggestResult = GeneralUtility::makeInstance(
                SuggestResultSet::class,
                $result['suggestions'] ?? [],
                $result['suggestion'] ?? ''
            );

            return $this->jsonResponse(json_encode($suggestResult));
        } catch (SolrUnavailableException $e) {
            return $this->handleSolrUnavailable();
        }
    }

    /**
     * Rendered when no search is available.
     * @return ResponseInterface
     */
    public function solrNotAvailableAction(): ResponseInterface
    {
        return $this->responseFactory->createResponse(503, self::STATUS_503_MESSAGE)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody($this->streamFactory->createStream(json_encode(['status' => 503, 'message' => ''])));
    }

}
