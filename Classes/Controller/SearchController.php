<?php

namespace Netlogix\Nxsolrajax\Controller;

use ApacheSolrForTypo3\Solr\Domain\Search\Suggest\SuggestService;
use ApacheSolrForTypo3\Solr\System\Solr\SolrUnavailableException;
use ApacheSolrForTypo3\Solr\Util;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SearchResultSet;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SuggestResultSet;
use Netlogix\Nxsolrajax\Event\Search\AfterGetSuggestionsEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class SearchController extends \ApacheSolrForTypo3\Solr\Controller\SearchController
{
    /**
     * @return string
     */
    public function indexAction()
    {
        try {
            $searchResultSet = $this->getSearchResultSet();
            $this->applyHttpHeadersToResponse();
        } catch (SolrUnavailableException $e) {
            $this->handleSolrUnavailable();
        }

        if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            $this->response->setHeader('Content-Type', 'application/json; charset=utf-8', true);
            return json_encode($searchResultSet, JSON_PRETTY_PRINT);
        } else {
            if ($searchResultSet instanceof SearchResultSet) {
                $searchResultSet->forceAddFacetData(true);
            }
            $jsonData = json_encode($searchResultSet);
            $this->view->assign('resultSet', json_decode($jsonData, true));
            $this->view->assign('resultSetJson', $jsonData);
        }
    }

    /**
     * @return string
     */
    public function resultsAction()
    {
        try {
            $searchResultSet = $this->getSearchResultSet();
            $this->applyHttpHeadersToResponse();
            return json_encode($searchResultSet, JSON_PRETTY_PRINT);
        } catch (SolrUnavailableException $e) {
            $this->handleSolrUnavailable();
        }
    }

    /**
     * @return \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet
     */
    protected function getSearchResultSet()
    {
        $arguments = (array)$this->request->getArguments();
        $pageId = $this->typoScriptFrontendController->getRequestedId();
        $languageId = Util::getLanguageUid();
        $searchRequest = $this->getSearchRequestBuilder()->buildForSearch($arguments, $pageId, $languageId);

        $searchResultSet = $this->searchService->search($searchRequest);

        // we pass the search result set to the controller context, to have the possibility
        // to access it without passing it from partial to partial
        $this->controllerContext->setSearchResultSet($searchResultSet);

        return $searchResultSet;
    }

    /**
     * This method creates a suggest json response that can be used in a suggest layer.
     *
     * @return string
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    public function suggestAction()
    {
        $queryString = $this->request->getArgument('q');
        $rawQuery = htmlspecialchars(mb_strtolower(trim($queryString)));

        $additionalFilters = $this->request->hasArgument('filters') ? $this->request->getArgument('filters') : [];

        try {
            /** @var SuggestService $suggestService */
            $suggestService = GeneralUtility::makeInstance(
                SuggestService::class,
                $this->typoScriptFrontendController,
                $this->searchService, $this->typoScriptConfiguration
            );

            $pageId = $this->typoScriptFrontendController->getRequestedId();
            $languageId = Util::getLanguageUid();
            $arguments = (array)$this->request->getArguments();
            $searchRequest = $this->getSearchRequestBuilder()->buildForSuggest($arguments, $rawQuery, $pageId, $languageId);
            $result = $suggestService->getSuggestions($searchRequest, $additionalFilters);


            $event = new AfterGetSuggestionsEvent($queryString, $result['suggestions'] ?? [], $this->typoScriptConfiguration);
            /** @var AfterGetSuggestionsEvent $event */
            $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch($event);
            $result['suggestions'] = $event->getSuggestions();


            $suggestResult = GeneralUtility::makeInstance(SuggestResultSet::class, $result['suggestions'], $result['suggestion']);

            $this->response->setHeader('Content-Type', 'application/json; charset=utf-8', true);
            return json_encode($suggestResult);

        } catch (SolrUnavailableException $e) {
            $this->handleSolrUnavailable();
        }
    }

    /**
     * Rendered when no search is available.
     * @return string
     */
    public function solrNotAvailableAction()
    {
        parent::solrNotAvailableAction();
        return json_encode(['status' => 503, 'message' => '']);
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    protected function applyHttpHeadersToResponse()
    {
        // Allow to cache the uncached plugin and manuel send cache Headers
        $tsfe = $this->getTypoScriptFrontendController();

        // Backup TSFE state
        $noCache = $tsfe->no_cache;
        $intincscript = $tsfe->config['INTincScript'];

        $tsfe->no_cache = false;
        $tsfe->config['config']['sendCacheHeaders'] = true;
        unset($tsfe->config['INTincScript']);

        foreach ($tsfe->applyHttpHeadersToResponse(new Response())->getHeaders() as $name => $values) {
            $this->response->setHeader($name, implode(', ', $values), true);
        }

        // Restore TSFE
        $tsfe->no_cache = $noCache;
        $tsfe->config['INTincScript'] = $intincscript;
        $tsfe->config['config']['sendCacheHeaders'] = false;
    }

}
