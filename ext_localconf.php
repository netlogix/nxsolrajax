<?php

/** @noinspection PhpFullyQualifiedNameUsageInspection */
defined('TYPO3') or die();

call_user_func(function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Nxsolrajax',
        'index',
        [\Netlogix\Nxsolrajax\Controller\SearchController::class => 'index'],
        [\Netlogix\Nxsolrajax\Controller\SearchController::class => 'index']
    );
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Nxsolrajax',
        'results',
        [\Netlogix\Nxsolrajax\Controller\SearchController::class => 'results'],
        [\Netlogix\Nxsolrajax\Controller\SearchController::class => 'results']
    );
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Nxsolrajax',
        'suggest',
        [\Netlogix\Nxsolrajax\Controller\SearchController::class => 'suggest'],
        [\Netlogix\Nxsolrajax\Controller\SearchController::class => 'suggest']
    );

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['searchResultClassName '] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\SearchResult::class;
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['searchResultSetClassName '] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\SearchResultSet::class;

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Options\OptionsFacet::class]['className'] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Options\OptionsFacet::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Options\Option::class]['className'] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Options\Option::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\QueryGroupFacet::class]['className'] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\QueryGroupFacet::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\Option::class]['className'] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\Option::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\HierarchyFacet::class]['className'] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\HierarchyFacet::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\Node::class]['className'] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\Node::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRangeFacet::class]['className'] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRangeFacet::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRange::class]['className'] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRange::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Sorting\Sorting::class]['className'] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\Sorting\Sorting::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Grouping\Group::class]['className'] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping\Group::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Grouping\GroupItem::class]['className'] = \Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping\GroupItem::class;
});
