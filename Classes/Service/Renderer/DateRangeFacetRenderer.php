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
			$result['url'] = $this->buildResetFacetUrl();
		} else {
			$result['url'] = $this->buildAddFacetUrl($this->facetName);
		}

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

		$result['active'] = $this->active;
		$result['start'] = $this->active ? date($facetConfiguration['format'], strtotime($facetOptions['start'])) : '';
		$result['end'] = $this->active ? date($facetConfiguration['format'], strtotime($facetOptions['end'])) : '';
		$result['value'] = $this->active ? $facetOptions['start'] . \tx_solr_query_filterencoder_DateRange::DELIMITER . $facetOptions['end'] : '';
		$result['min'] = date($facetConfiguration['format'], strtotime(current($counts)));
		$result['max'] = date($facetConfiguration['format'], strtotime(end($counts)));

		return $result;
	}

}