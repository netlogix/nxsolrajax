<?php
namespace Netlogix\Nxsolrajax\Controller;

	/***************************************************************
	 *  Copyright notice
	 *  (c) 2014 Sascha Nowak <sascha.nowak@netlogix.de>, netlogix GmbH & Co. KG
	 *  All rights reserved
	 *  This script is part of the TYPO3 project. The TYPO3 project is
	 *  free software; you can redistribute it and/or modify
	 *  it under the terms of the GNU General Public License as published by
	 *  the Free Software Foundation; either version 3 of the License, or
	 *  (at your option) any later version.
	 *  The GNU General Public License can be found at
	 *  http://www.gnu.org/copyleft/gpl.html.
	 *  This script is distributed in the hope that it will be useful,
	 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
	 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 *  GNU General Public License for more details.
	 *  This copyright notice MUST APPEAR in all copies of the script!
	 ***************************************************************/

/**
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class SuggestController implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected $typoscriptFrontendController;

	/**
	 * @var \Tx_Solr_SuggestQuery
	 */
	protected $suggestQuery;

	/**
	 * @var \Tx_Solr_Search
	 */
	protected $search;

	/**
	 * @var array
	 */
	protected $settings;

	protected function initializeObject() {

		if (!is_object($GLOBALS['TSFE'])) {
			$pageId = filter_var(\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('id'), FILTER_SANITIZE_NUMBER_INT);
			/** @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $typoscriptFrontendController */
			$typoscriptFrontendController = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', $GLOBALS['TYPO3_CONF_VARS'], $pageId, 0, TRUE);
			$this->injectTyposcriptFrontendController($typoscriptFrontendController);
		} else {
			$this->typoscriptFrontendController = $GLOBALS['TSFE'];
		}

		$this->injectSettings(\Tx_Solr_Util::getSolrConfiguration());

		$q = trim(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('q'));
		/** @var \Tx_Solr_SuggestQuery $suggestQuery */
		$suggestQuery = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_SuggestQuery', $q);
		$this->injectSuggestQuery($suggestQuery);

		/** @var \Tx_Solr_SolrService $solrService */
		$solrService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_ConnectionManager')->getConnectionByPageId($this->typoscriptFrontendController->id, $this->typoscriptFrontendController->sys_language_uid);
		$this->injectSolrService($solrService);

	}

	/**
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $typoscriptFrontendController
	 */
	protected function injectTyposcriptFrontendController(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $typoscriptFrontendController) {
		$languageId = filter_var(\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('L'), FILTER_VALIDATE_INT, array('options' => array('default' => 0, 'min_range' => 0)));

		$GLOBALS['TSFE'] = $typoscriptFrontendController;
		$typoscriptFrontendController->initFEuser();
		$typoscriptFrontendController->initUserGroups();
		$typoscriptFrontendController->sys_page = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
		$typoscriptFrontendController->rootLine = $typoscriptFrontendController->sys_page->getRootline($typoscriptFrontendController->id, '');
		$typoscriptFrontendController->initTemplate();
		$typoscriptFrontendController->getConfigArray();

		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->loadCachedTca();
		$typoscriptFrontendController->sys_language_uid = $languageId;

		$this->typoscriptFrontendController = $typoscriptFrontendController;
	}

	/**
	 * @param array $setting
	 */
	protected function injectSettings($setting) {
		$this->settings = $setting;
	}

	/**
	 * @param \Tx_Solr_SuggestQuery $suggestQuery
	 */
	protected function injectSuggestQuery(\Tx_Solr_SuggestQuery $suggestQuery) {
		$allowedSites = \Tx_Solr_Util::resolveSiteHashAllowedSites($GLOBALS['TSFE']->id, $this->settings['search.']['query.']['allowedSites']);

		$suggestQuery->setUserAccessGroups(explode(',', $this->typoscriptFrontendController->gr_list));
		$suggestQuery->addFilter('(endtime:[NOW/MINUTE TO *] OR endtime:"' . \Tx_Solr_Util::timestampToIso(0) . '")');
		$suggestQuery->setSiteHashFilter($allowedSites);
		$suggestQuery->setOmitHeader();

		foreach ($this->getAdditionalFilters() as $additionalFilter) {
			$suggestQuery->addFilter($additionalFilter);
		}

		$this->suggestQuery = $suggestQuery;
	}

	/**
	 * @param \Tx_Solr_SolrService $solrService
	 */
	protected function injectSolrService(\Tx_Solr_SolrService $solrService) {
		$this->search = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_Search', $solrService);
	}

	/**
	 * @return string
	 */
	public function dispatch() {

		$response = json_encode($this->suggestAction());

		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');
		header('Content-Length: ' . strlen($response));
		header('Content-Type: application/json; charset=utf-8');
		header('Content-Transfer-Encoding: 8bit');
		echo $response;
	}

	/**
	 * @return array
	 */
	public function suggestAction() {
		$this->initializeObject();
		$response = array('status' => 'error');

		if ($this->search->ping()) {
			$response['status'] = 'ok';

			$results = json_decode($this->search->search($this->suggestQuery, 0, 0)->getRawResponse());
			$facetSuggestions = $results->facet_counts->facet_fields->{$this->settings['suggest.']['suggestField']};
			$facetSuggestions = get_object_vars($facetSuggestions);

			$suggestions = array();
			foreach ($facetSuggestions as $partialKeyword => $value) {
				$suggestionKey = trim($this->suggestQuery->getKeywords() . ' ' . $partialKeyword);
				$suggestions[] = array('name' => $suggestionKey, 'count' => $value,);
			}

			$response['results'] = $suggestions;
		}

		return $response;
	}

	/**
	 * @return array
	 */
	protected function getAdditionalFilters() {
		$additionalFilters = array();

		if (!empty($this->settings['search.']['query.']['filter.'])) {
			foreach ($this->settings['search.']['query.']['filter.'] as $filterKey => $filter) {
				if (!is_array($this->settings['search.']['query.']['filter.'][$filterKey])) {
					if (is_array($this->settings['search.']['query.']['filter.'][$filterKey . '.'])) {
						$filter = $this->typoscriptFrontendController->cObj->stdWrap($this->settings['search.']['query.']['filter.'][$filterKey], $this->settings['search.']['query.']['filter.'][$filterKey . '.']);
					}

					$additionalFilters[$filterKey] = $filter;
				}
			}
		}

		$solrParameter = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('tx_solr');
		if (!empty($solrParameter['filter'])) {
			foreach($solrParameter['filter'] as $filter) {
				list($filterKey, $filterValue) = explode(':', $filter);
				if (is_array($this->settings['search.']['faceting.']['facets.'][$filterKey . '.'])) {
					$facetConfig = $this->settings['search.']['faceting.']['facets.'][$filterKey . '.'];
					if ($facetConfig['type'] == 'hierarchy') {
						$filterValue = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_Query_FilterEncoder_Hierarchy')->decodeFilter($filterValue, $facetConfig);
					}
					$additionalFilters[] = $facetConfig['field'] . ':' . $filterValue;
				}

			}
		}

		return $additionalFilters;
	}

}

/** @var SuggestController $suggestController */
$suggestController = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Netlogix\\Nxsolrajax\\Controller\\SuggestController');
$suggestController->dispatch();