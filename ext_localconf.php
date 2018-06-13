<?php
defined('TYPO3_MODE') or die();

call_user_func(function () {

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('Netlogix.Nxsolrajax', 'index', ['Search' => 'index'], ['Search' => '']);
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('Netlogix.Nxsolrajax', 'results', ['Search' => 'results'], ['Search' => 'results']);
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('Netlogix.Nxsolrajax', 'suggest', ['Search' => 'suggest'], ['Search' => 'suggest']);

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['searchResultClassName '] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\SearchResult::class;
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['searchResultSetClassName '] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\SearchResultSet::class;

    \ApacheSolrForTypo3\Solr\Search\SearchComponentManager::registerSearchComponent('defaultFacetSelection', \Netlogix\Nxsolrajax\Search\DefaultFacetSelectionComponent::class);

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Options\OptionsFacet::class]['className'] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Options\OptionsFacet::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Options\Option::class]['className'] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Options\Option::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\QueryGroupFacet::class]['className'] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\QueryGroupFacet::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\Option::class]['className'] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\Option::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\HierarchyFacet::class]['className'] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\HierarchyFacet::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\Node::class]['className'] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\Node::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRangeFacet::class]['className'] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRangeFacet::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRange::class]['className'] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRange::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Sorting\Sorting::class]['className'] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\Sorting\Sorting::class;

});
