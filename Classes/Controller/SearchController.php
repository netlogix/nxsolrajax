<?php
namespace Netlogix\Nxsolrajax\Controller;

use ApacheSolrForTypo3\Solr\SuggestQuery;
use ApacheSolrForTypo3\Solr\Util;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SuggestResultSet;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Response;

class SearchController extends \ApacheSolrForTypo3\Solrfluid\Controller\SearchController
{

    /**
     * @return string
     */
    public function indexAction()
    {
    }

    /**
     * @return string
     */
    public function resultsAction()
    {
        if (!$this->searchService->getIsSolrAvailable()) {
            $this->forward('solrNotAvailable');
        }

        // perform the current search.
        $this->searchService->setUsePluginAwareComponents(false);
        $searchRequest = $this->buildSearchRequest();
        $searchResultSet = $this->searchService->search($searchRequest);

        // we pass the search result set to the controller context, to have the possibility
        // to access it without passing it from partial to partial
        $this->controllerContext->setSearchResultSet($searchResultSet);

        return json_encode($searchResultSet);
    }

    /**
     * @return string
     */
    public function suggestAction()
    {
        if (!$this->searchService->getIsSolrAvailable()) {
            $this->forward('solrNotAvailable');
        }
        $search = $this->searchService->getSearch();
        $suggestQuery = $this->buildSuggestQuery();
        $response = $search->search($suggestQuery);
        $suggestField = $this->typoScriptConfiguration->getValueByPath('plugin.tx_solr.suggest.suggestField');
        $facetSuggestions = get_object_vars($response->facet_counts->facet_fields->{$suggestField});
        $result = GeneralUtility::makeInstance(SuggestResultSet::class, $facetSuggestions, $suggestQuery->getKeywords());
        return json_encode($result);
    }

    /**
     * @return SuggestQuery
     */
    protected function buildSuggestQuery()
    {
        $q = $this->request->getArgument('q');
        $allowedSites = Util::resolveSiteHashAllowedSites(
            $GLOBALS['TSFE']->id,
            $this->typoScriptConfiguration->getValueByPath('plugin.tx_solr.search.query.allowedSites')
        );

        $suggestQuery = GeneralUtility::makeInstance(SuggestQuery::class, $q);
        $suggestQuery->setUserAccessGroups(explode(',', $GLOBALS['TSFE']->gr_list));
        $suggestQuery->setSiteHashFilter($allowedSites);
        $suggestQuery->setOmitHeader();

        return $suggestQuery;
    }

    /**
     * Rendered when no search is available.
     * @return string
     */
    public function solrNotAvailableAction()
    {
        if ($this->response instanceof Response) {
            $this->response->setStatus(503);
        }
        return json_encode(['status' => 503, 'message' => '']);
    }

}
