<?php
namespace Netlogix\Nxsolrajax\Domain\Model\Dto;

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
class Facet implements \Netlogix\Nxcrudextbase\Domain\Model\DataTransfer\DataTransferInterface, \Netlogix\Nxcrudextbase\Domain\Model\DataTransfer\SkipCachingInterface {

	/**
	 * @var array
	 */
	protected $innermostSelf;

	/**
	 * @var array
	 */
	protected $usedFacets = array();

	/**
	 * @var array
	 */
	protected $availableFacets = array();

	/**
	 * @param array $innermostSelf
	 */
	public function __construct($innermostSelf) {
		$this->innermostSelf = $innermostSelf;
	}

	/**
	 * Returns all properties that should be exposed by JsonView
	 * @return array<string>
	 */
	public function getPropertyNamesToBeApiExposed() {
		return array('usedFacets', 'availableFacets');
	}

	/**
	 * This object is where this DataTransfer Object is wrapped around. It should *not* be
	 * exposed, because that is the only purpose of this DataTransfer Object object.
	 * @return \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject
	 */
	public function getInnermostSelf() {
		$this->innermostSelf;
	}

	/**
	 * @return array
	 */
	public function getUsedFacets() {

		if (!$this->innermostSelf['usedFacets']['removeFacet']) {
			return array();
		}

		$facetOptions = array(
			'removeAll' => array(
				'label' => $this->innermostSelf['usedFacets']['removeAllFacets']['text'],
				'url' => $this->innermostSelf['usedFacets']['removeAllFacets']['url']
			),
			'options' => array(),
		);

		foreach ($this->innermostSelf['usedFacets']['removeFacet'] as $rawUsedFacetOption) {
			$facetOptions['options'][] = array(
				'text' => $rawUsedFacetOption['text'],
				'url' => $rawUsedFacetOption['url'],
			);
		}

		return $facetOptions;
	}

	/**
	 * @return array
	 */
	public function getAvailableFacets() {
		$facetOptions = array();

		foreach ($this->innermostSelf['availableFacets'] as $rawFacetOption) {
			$facetOption = array(
				'label' => $rawFacetOption['facet']['label'],
				'type' => $rawFacetOption['type'],
				'active' => FALSE,
				'resetUrl' => '',
				'options' => array()
			);

			if ($rawFacetOption['singleFacetOption']['facetlinks']) {
				foreach ($rawFacetOption['singleFacetOption']['facetlinks'] as $facetLink) {
					$facetOption['options'][] = array(
						'text' => $facetLink['text'],
						'url' => htmlspecialchars_decode($facetLink['url']),
						'count' => $facetLink['count'],
						'selected' => intval($facetLink['selected']),
					);
					if ($facetLink['selected']) {
						$facetOption['active'] = TRUE;
						$facetOption['resetUrl'] = htmlspecialchars_decode($facetLink['url']);
					}
				}
			} else {
				$facetOption['options'] = $rawFacetOption['singleFacetOption'];
				foreach ($rawFacetOption['singleFacetOption'] as $facetLink) {
					if ($facetLink['active']) {
						$facetOption['active'] = TRUE;
						$facetOption['resetUrl'] =  htmlspecialchars_decode($facetLink['reseturl']);
					}
				}
			}

			$facetOptions[] = $facetOption;
		}

		return $facetOptions;
	}

}