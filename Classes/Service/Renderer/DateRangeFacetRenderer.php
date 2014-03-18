<?php
namespace Netlogix\Nxsolrajax\Service\Renderer;

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
class DateRangeFacetRenderer extends \Tx_Solr_Facet_DateRangeFacetRenderer {

	/**
	 * @var bool
	 */
	protected $active = false;

	/**
	 * @return string|void
	 */
	public function renderFacetOptions() {
		if (!$this->facetConfiguration['renderingType'] == 'json') {
			return parent::renderFacetOptions();
		}
		$this->active = $this->facet->isActive();
		$result = $this->getHandlePositions();

		if ($this->active) {
			$result['resetUrl'] = $this->buildResetFacetUrl();
		}
		$result['url'] = $this->buildAddFacetUrl();
		$result['delimiter'] = \tx_solr_query_filterencoder_DateRange::DELIMITER;

		return $result;
	}

	/**
	 * Gets the handle positions for the datePicker.
	 *
	 * @return array Array with keys start and end
	 */
	protected function getHandlePositions() {
		$result = array();

		$facetConfiguration = $this->facetConfiguration['dateRange.'];
		$facetOptions = $this->getFacetOptions();
		$counts = array_keys((array)$facetOptions['counts']);

		$result['selected'] = $this->active;
		$result['format'] = $facetConfiguration['jsFormat'] ?: 'MM/dd/yyyy';
		$result['start'] = '';
		$result['end'] = '';
		$result['min'] = date($facetConfiguration['format'], strtotime(current($counts)));
		$result['max'] = date($facetConfiguration['format'], strtotime(end($counts)));

		$filters = $this->search->getQuery()->getFilters();
		foreach ($filters as $filter) {
			if (preg_match('/\(' . $this->facetConfiguration['field'] . ':\[(.*)\]\)/', $filter, $matches) ){
				$range = explode('TO', $matches[1]);
				$range = array_map('trim', $range);

				$result['start'] = ($range[0] == '*') ? '' : date($facetConfiguration['format'], \Tx_Solr_Util::isoToTimestamp($range[0]));
				$result['end'] = ($range[1] == '*') ? '' : date($facetConfiguration['format'], \Tx_Solr_Util::isoToTimestamp($range[1]));
				break;
			}
		}

		return $result;
	}

	/**
	 * tbd
	 */
	protected function buildAddFacetUrl() {
		/** @var $facetOption \Tx_Solr_Facet_FacetOption */
		$facetOption      = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_Facet_FacetOption', $this->facetName, '{filterValue}');
		/** @var $linkBuilder \tx_solr_facet_LinkBuilder */
		$facetLinkBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_Facet_LinkBuilder', $this->search->getQuery(), $this->facetName, $facetOption);
		$facetLinkBuilder->setLinkTargetPageId($this->linkTargetPageId);

		return htmlspecialchars_decode($facetLinkBuilder->getReplaceFacetOptionUrl());
	}

}