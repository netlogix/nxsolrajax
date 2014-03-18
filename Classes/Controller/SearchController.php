<?php
namespace Netlogix\Nxsolrajax\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Sascha Nowak <sascha.nowak@netlogix.de>, netlogix GmbH & Co. KG
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class SearchController extends \Netlogix\Nxcrudextbase\Controller\AbstractRestController {

	/**
	 * @var \Netlogix\Nxsolrajax\Service\QueryFactory
	 */
	protected $queryFactory;

	/**
	 * @var \Tx_Solr_Query
	 */
	protected $query;

	/**
	 * @var \Tx_Solr_Search
	 */
	protected $search;

	/**
	 * @param \Netlogix\Nxsolrajax\Service\QueryFactory $queryFactory
	 */
	public function injectQueryFactory(\Netlogix\Nxsolrajax\Service\QueryFactory $queryFactory) {
		$this->queryFactory = $queryFactory;
	}

	public function initializeAction() {
		$this->query = $this->queryFactory->getQuery();
		$this->search = $this->queryFactory->getSearch();
	}

	/**
	 * @param integer $page
	 *
	 * @return void
	 */
	public function searchAction($page = 0) {
		if ($this->query !== NULL) {

			$offSet = $page * $this->query->getResultsPerPage();

			// performing the actual search, sending the query to the Solr server
			$this->search->search($this->query, $offSet, NULL);

			$result = $this->getMoreLinks($page);

			$result = array('facets' => $this->processFacets(), 'result' => $this->processResult($result), 'search' => $this->processSearch());

			$this->view->assign('object', $result);
		}
	}

	/**
	 * @param integer $page
	 *
	 * @return void
	 */
	public function moreResultsAction($page) {
		if ($this->query !== NULL) {

			$offSet = $page * $this->query->getResultsPerPage();

			// performing the actual search, sending the query to the Solr server
			$this->search->search($this->query, $offSet, NULL);

			$result = $this->getMoreLinks($page);

			$result = array('result' => $this->processResult($result), 'search' => $this->processSearch());
			$this->view->assign('object', $result);
		}
	}

	/**
	 * @param integer $page
	 *
	 * @return array
	 */
	protected function getMoreLinks($page) {
		$links = array();

		if ($page > 0) {
			$links['prevLink'] = $this->uriBuilder->reset()->setAddQueryString(TRUE)->uriFor('moreResults', array('page' => $page - 1, 'isAjax' => 1));
		}

		$resultsPerPage = $this->query->getResultsPerPage();
		$resultOffset = $this->search->getResultOffset();
		$numberOfResults = $this->search->getNumberOfResults();

		if ($numberOfResults - $resultsPerPage > $resultOffset) {
			$links['nextLink'] = $this->uriBuilder->reset()->setAddQueryString(TRUE)->uriFor('moreResults', array('page' => $page + 1, 'isAjax' => 1));
		}

		return $links;
	}

	/**
	 * @return array
	 */
	protected function processFacets() {

		/** @var \Netlogix\Nxsolrajax\Service\Processor\FacetProcessor $facetProcessor */
		$facetProcessor = $this->objectManager->get('Netlogix\\Nxsolrajax\\Service\\Processor\\FacetProcessor');

		return $facetProcessor->processResult();
	}

	/**
	 * @param array $result
	 *
	 * @return array
	 */
	protected function processResult($result = array()) {

		/** @var \Netlogix\Nxsolrajax\Service\Processor\ResultProcessor $resultProcessor */
		$resultProcessor = $this->objectManager->get('Netlogix\\Nxsolrajax\\Service\\Processor\\ResultProcessor');

		return $resultProcessor->processResult($result);
	}

	/**
	 * @param array $result
	 *
	 * @return array
	 */
	protected function processSearch($result = array()) {

		/** @var \Netlogix\Nxsolrajax\Service\Processor\SearchProcessor $resultProcessor */
		$resultProcessor = $this->objectManager->get('Netlogix\\Nxsolrajax\\Service\\Processor\\SearchProcessor');

		$result['url'] = $this->uriBuilder->reset()->uriFor('search', array('isAjax' => 1));
		return $resultProcessor->processResult($result);
	}
} 