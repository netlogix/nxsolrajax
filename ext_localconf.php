<?php
defined('TYPO3_MODE') or die();

call_user_func(function () {

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('Netlogix.Nxsolrajax', 'index', ['Search' => 'index'], ['Search' => '']);
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('Netlogix.Nxsolrajax', 'results', ['Search' => 'results'], ['Search' => 'results']);

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['searchResultClassName '] = 'Netlogix\\Nxsolrajax\\Domain\\Search\\ResultSet\\SearchResult';
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['searchResultSetClassName '] = 'Netlogix\\Nxsolrajax\\Domain\\Search\\ResultSet\\SearchResultSet';

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['ApacheSolrForTypo3\\Solrfluid\\Domain\\Search\\ResultSet\\Facets\\OptionBased\\Options\\OptionsFacet']['className'] = 'Netlogix\\Nxsolrajax\\Domain\\Search\\ResultSet\\Facets\\OptionBased\\Options\\OptionsFacet';
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['ApacheSolrForTypo3\\Solrfluid\\Domain\\Search\\ResultSet\\Facets\\OptionBased\\Options\\Option']['className'] = 'Netlogix\\Nxsolrajax\\Domain\\Search\\ResultSet\\Facets\\OptionBased\\Options\\Option';
});
