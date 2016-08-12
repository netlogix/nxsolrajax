<?php
namespace Netlogix\Nxsolrajax\Controller;

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

    }

}
