<?php

declare(strict_types=1);

use Netlogix\Nxsolrajax\Controller\SearchController;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\HierarchyFacet;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\Node;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Options\Option;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Options\OptionsFacet;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\Option as QueryGroupOption;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\QueryGroupFacet;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRange;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRangeFacet;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\RangeBased\NumericRange\NumericRangeFacet;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping\Group;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Grouping\GroupItem;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SearchResult;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SearchResultSet;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Sorting\Sorting;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

ExtensionUtility::configurePlugin(
    'Nxsolrajax',
    'index',
    [SearchController::class => 'index'],
    [SearchController::class => 'index'],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);

ExtensionUtility::configurePlugin(
    'Nxsolrajax',
    'results',
    [SearchController::class => 'results'],
    [SearchController::class => 'results'],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);

ExtensionUtility::configurePlugin(
    'Nxsolrajax',
    'suggest',
    [SearchController::class => 'suggest'],
    [SearchController::class => 'suggest'],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['searchResultClassName '] = SearchResult::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['searchResultSetClassName '] = SearchResultSet::class;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][
    \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Options\OptionsFacet::class
]['className'] = OptionsFacet::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][
    \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Options\Option::class
]['className'] = Option::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][
    \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\QueryGroupFacet::class
]['className'] = QueryGroupFacet::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][
    \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\Option::class
]['className'] = QueryGroupOption::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][
    \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\HierarchyFacet::class
]['className'] = HierarchyFacet::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][
    \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\Node::class
]['className'] = Node::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][
    \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRangeFacet::class
]['className'] = DateRangeFacet::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][
    \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRange::class
]['className'] = DateRange::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][
    \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\RangeBased\NumericRange\NumericRangeFacet::class
]['className'] = NumericRangeFacet::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][
    \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Sorting\Sorting::class
]['className'] = Sorting::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][
    \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Grouping\Group::class
]['className'] = Group::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][
    \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Grouping\GroupItem::class
]['className'] = GroupItem::class;
