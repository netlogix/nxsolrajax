<?php

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['Tx_Solr_Facet_HierarchicalFacetRenderer']['className'] = 'Netlogix\\Nxsolrajax\\Service\\Renderer\\HierarchicalFacetRenderer';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['Tx_Solr_Facet_DateRangeFacetRenderer']['className'] = 'Netlogix\\Nxsolrajax\\Service\\Renderer\\DateRangeFacetRenderer';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['Tx_Solr_Query_FilterEncoder_DateRange']['className'] = 'Netlogix\\Nxsolrajax\\Service\\Query\\FilterEncoder\\DateRange';

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
// Registering suggest eID
$TYPO3_CONF_VARS['FE']['eID_include']['tx_nxsolrajax_suggest'] = 'EXT:nxsolrajax/Classes/Controller/SuggestController.php';

?>