<?php

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['Tx_Solr_Facet_HierarchicalFacetRenderer']['className'] = 'Netlogix\\Nxsolrajax\\Service\\Renderer\\HierarchicalFacetRenderer';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['Tx_Solr_Facet_DateRangeFacetRenderer']['className'] = 'Netlogix\\Nxsolrajax\\Service\\Renderer\\DateRangeFacetRenderer';

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'Netlogix.' . $_EXTKEY,
	'AjaxSearch',
	array(
		'Search' => 'search, moreResults'
	),
	array(
		'Search' => 'search, moreResults'
	)
);

?>