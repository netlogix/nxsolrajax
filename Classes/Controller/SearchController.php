<?php

namespace Netlogix\Nxsolrajax\Controller;

use ApacheSolrForTypo3\Solr\Domain\Search\Suggest\SuggestService;
use ApacheSolrForTypo3\Solr\System\Solr\SolrUnavailableException;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SuggestResultSet;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SearchController extends \ApacheSolrForTypo3\Solr\Controller\SearchController
{
    /**
     * @return string
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function indexAction()
    {
        if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            $this->forward('results');
        }
    }

    /**
     * @return string
     */
    public function resultsAction()
    {
        try {
            $arguments = (array)$this->request->getArguments();
            $pageId = $this->typoScriptFrontendController->getRequestedId();
            $languageId = $this->typoScriptFrontendController->sys_language_uid;
            $searchRequest = $this->getSearchRequestBuilder()->buildForSearch($arguments, $pageId, $languageId);

            $searchResultSet = $this->searchService->search($searchRequest);

            // we pass the search result set to the controller context, to have the possibility
            // to access it without passing it from partial to partial
            $this->controllerContext->setSearchResultSet($searchResultSet);

            return json_encode($searchResultSet);

        } catch (SolrUnavailableException $e) {
            $this->handleSolrUnavailable();
        }
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

        try {
            /** @var SuggestService $suggestService */
            $suggestService = GeneralUtility::makeInstance(
                SuggestService::class,
                $this->typoScriptFrontendController,
                $this->searchService, $this->typoScriptConfiguration
            );
            $additionalFilters = htmlspecialchars(GeneralUtility::_GET('filters'));

            $pageId = $this->typoScriptFrontendController->getRequestedId();
            $languageId = $this->typoScriptFrontendController->sys_language_uid;
            $arguments = (array)$this->request->getArguments();
            $searchRequest = $this->getSearchRequestBuilder()->buildForSuggest($arguments, $rawQuery, $pageId, $languageId);
            $result = $suggestService->getSuggestions($searchRequest, $additionalFilters);

            $suggestResult = GeneralUtility::makeInstance(SuggestResultSet::class, $result['suggestions'], $result['suggestion']);
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

}
