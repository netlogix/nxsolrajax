<?php

namespace Netlogix\Nxsolrajax\Controller;

use ApacheSolrForTypo3\Solr\Domain\Search\Suggest\SuggestService;
use ApacheSolrForTypo3\Solr\System\Solr\SolrUnavailableException;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SearchResultSet;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SuggestResultSet;
use Netlogix\Nxsolrajax\SugesstionResultModifier;
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

            // Allow to cache the uncached plugin and manuell send cache Headers
            $tsfe = $this->getTypoScriptFrontendController();
            $tsfe->config['config']['sendCacheHeaders'] = false;
            if (!empty($tsfe->config['config']['sendCacheHeaders']) && !$tsfe->beUserLogin) {
                $this->response->setHeader('Expires', gmdate('D, d M Y H:i:s T', $tsfe->cacheExpires), true);
                $this->response->setHeader('Cache-Control', 'max-age=' . ($tsfe->cacheExpires - $GLOBALS['EXEC_TIME']), true);
                $this->response->setHeader('Pragma', 'public', true);
            }
        } catch (SolrUnavailableException $e) {
            $this->handleSolrUnavailable();
        }

        if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            return json_encode($searchResultSet);
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
            return json_encode($searchResultSet);
        } catch (SolrUnavailableException $e) {
            $this->handleSolrUnavailable();
        }
    }

    /**
     * @return \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet
     */
    private function getSearchResultSet()
    {
        $arguments = (array)$this->request->getArguments();
        $pageId = $this->typoScriptFrontendController->getRequestedId();
        $languageId = $this->typoScriptFrontendController->sys_language_uid;
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

            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nxsolrajax']['modifySuggestions'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nxsolrajax']['modifySuggestions'] as $key => $classRef) {
                    $hookObject = GeneralUtility::makeInstance($classRef);
                    if (!$hookObject instanceof SugesstionResultModifier) {
                        throw new \Exception(sprintf('modifySuggestions hook expects SuggestionResultModifier, got %s', get_class($hookObject)), 1533224243);
                    }
                    $result['suggestions'] = $hookObject->modifySuggestions($queryString, is_array($result['suggestions']) ? $result['suggestions'] : [], $this->typoScriptConfiguration);
                }
            }

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

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

}
