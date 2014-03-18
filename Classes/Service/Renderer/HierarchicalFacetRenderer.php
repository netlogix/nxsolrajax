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
class HierarchicalFacetRenderer extends \Tx_Solr_Facet_HierarchicalFacetRenderer {

	/**
	 * Renders the complete hierarchical facet.
	 *
	 * @see Tx_Solr_Facet_AbstractFacetRenderer::renderFacetOptions()
	 * @return string Facet markup.
	 */
	protected function renderFacetOptions() {
		$facetContent = '';
		$facetOptions = $this->getFacetOptions();

		/* @var $filterEncoder \Tx_Solr_Query_FilterEncoder_Hierarchy */
		$filterEncoder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_Query_FilterEncoder_Hierarchy');

			// enrich the facet options with links before building the menu structure
		$enrichedFacetOptions = array();
		foreach ($facetOptions as $facetOptionValue => $facetOptionResultCount) {
			/** @var \Tx_Solr_Facet_FacetOption $facetOption */
			$facetOption = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_Facet_FacetOption',
				$this->facetName,
				$facetOptionValue,
				$facetOptionResultCount
			);

			$facetOption->setUrlValue($filterEncoder->encodeFilter($facetOptionValue));

			$facetLinkBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_Facet_LinkBuilder', $this->search->getQuery(), $this->facetName, $facetOption);

			$optionSelected = $facetOption->isSelectedInFacet($this->facetName);
			$optionLinkUrl  = $facetLinkBuilder->getAddFacetOptionUrl();

				// negating the facet option links to remove a filter
			if ($this->facetConfiguration['selectingSelectedFacetOptionRemovesFilter'] && $optionSelected) {
				$optionLinkUrl = $facetLinkBuilder->getRemoveFacetOptionUrl();
			}

			if ($this->facetConfiguration['singleOptionMode']) {
				$optionLinkUrl = $facetLinkBuilder->getReplaceFacetOptionUrl();
			}

				// by default the facet link builder creates htmlspecialchars()ed URLs
				// HMENU will also apply htmlspecialchars(), to prevent corrupt URLs
				// we're reverting the facet builder's htmlspecials() here
			$optionLinkUrl = htmlspecialchars_decode($optionLinkUrl);

			$enrichedFacetOptions[$facetOption->getValue()] = array(
				'numberOfResults' => $facetOption->getNumberOfResults(),
				'url'             => $optionLinkUrl,
				'selected'        => $optionSelected,
				'resetUrl'        => $optionSelected ? $facetLinkBuilder->getRemoveFacetOptionUrl() : '',
			);
		}

		return $this->renderHierarchicalFacet($enrichedFacetOptions);
	}

	/**
	 * @param array $facetOptions Available facet options.
	 * @return string Hierarchical facet rendered by a cObject
	 */
	protected function renderHierarchicalFacet($facetOptions) {
		if (!$this->facetConfiguration['renderingType'] == 'json') {
			return (string)parent::renderHierarchicalFacet($facetOptions);
		}

		return $this->getMenuStructure($facetOptions);
	}


	/**
	 * Builds a menu structure usable with HMENU and returns it.
	 *
	 * Starts with the top level menu entries and hands the sub menu building
	 * off to a recursive method.
	 *
	 * @param array $facetOptions
	 * @return array
	 */
	protected function getMenuStructure($facetOptions) {
		$menuStructure = array();

		foreach ($facetOptions as $facetOptionKey => $facetOption) {

				// let's start with top level menu options
			if (substr($facetOptionKey, 0, 1) == '0') {
				$topLevelMenu = array(
					'text'            => $this->getFacetOptionLabel($facetOptionKey, $facetOption['numberOfResults']),
					'url'             => $facetOption['url'],
					'resetUrl'        => $facetOption['resetUrl'],
					'count'           => $facetOption['numberOfResults'],
					'selected'        => $facetOption['selected'],
					'active'          => $facetOption['selected'],
				);

				list(, $mainMenuName) = explode('-', $facetOptionKey, 2);

					// build sub menus recursively
				$subMenu = $this->getSubMenu($facetOptions, $mainMenuName, 1);
				if (!empty($subMenu)) {
					$topLevelMenu['options'] = $subMenu;
					foreach ($subMenu as $option) {
						if ($option['selected'] || $option['active']) {
							$topLevelMenu['active'] = TRUE;
							$topLevelMenu['resetUrl'] = $option['resetUrl'];
						}
					}
				}

				$menuStructure[] = $topLevelMenu;
			}
		}

		return $menuStructure;
	}

	/**
	 * Recursively builds a sub menu structure for the current menu.
	 *
	 * @param array $facetOptions Array of facet options
	 * @param string $menuName Name of the top level menu to build the sub menu structure for
	 * @param integer $level The sub level depth
	 * @return array Returns an array sub menu structure if a sub menu exists, an empty array otherwise
	 */
	protected function getSubMenu(array $facetOptions, $menuName, $level) {
		$menu = array();

		$subMenuEntryPrefix = $level . '-' . $menuName;

		foreach ($facetOptions as $facetOptionKey => $facetOption) {
				// find the sub menu items for the current menu
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($facetOptionKey, $subMenuEntryPrefix)) {
				$currentMenu = array(
					'text'            => $this->getFacetOptionLabel($facetOptionKey, $facetOption['numberOfResults']),
					'url'             => $facetOption['url'],
					'resetUrl'        => $facetOption['resetUrl'],
					'count'           => $facetOption['numberOfResults'],
					'selected'        => $facetOption['selected'],
					'active'          => $facetOption['selected'],
				);

				$lastPathSegment = \Tx_Solr_Facet_HierarchicalFacetRenderer::getLastPathSegmentFromHierarchicalFacetOption($facetOptionKey);

					// move one level down (recursion)
				$subMenu = $this->getSubMenu(
					$facetOptions,
					$menuName . '/' . $lastPathSegment,
					$level + 1
				);
				if (!empty($subMenu)) {
					$currentMenu['options'] = $subMenu;
					foreach ($subMenu as $option) {
						if ($option['selected'] || $option['active']) {
							$currentMenu['active'] = TRUE;
							$currentMenu['resetUrl'] = $option['resetUrl'];
						}
					}
				}

				$menu[] = $currentMenu;
			}
		}

			// return one level up
		return $menu;
	}

	/**
	 * Generates a facet option label from the given facet option.
	 *
	 * @param string $facetOptionKey A hierachical facet option path
	 * @param integer $facetOptionResultCount
	 * @return string The label for the facet option consisting of the last part of the path and the options result count
	 */
	protected function getFacetOptionLabel($facetOptionKey, $facetOptionResultCount) {
			// use the last path segment and the result count to build the label
		$facetOptionLabel = \Tx_Solr_Facet_HierarchicalFacetRenderer::getLastPathSegmentFromHierarchicalFacetOption($facetOptionKey);

		if (isset($this->facetConfiguration['renderingInstruction'])) {
			/** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject */
			$contentObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
			$contentObject->start(array(
				'optionValue' => $facetOptionLabel,
				'optionCount' => $facetOptionResultCount,
			));

			$facetOptionLabel = $contentObject->cObjGetSingle(
				$this->facetConfiguration['renderingInstruction'],
				$this->facetConfiguration['renderingInstruction.']
			);
		}

		return htmlspecialchars($facetOptionLabel);
	}

	/**
	 * Takes the hierarchical facet option, splits it up and returns the last
	 * path segment from the hierarchy
	 *
	 * @param string $facetOptionKey A complete hierarchical facet option
	 * @return string The last path segment of the hierarchical facet option
	 */
	public static function getLastPathSegmentFromHierarchicalFacetOption($facetOptionKey) {
			// first remove the level indicator in front of the path
		$facetOptionKey = trim($facetOptionKey, '"');
		list(, $path) = explode('-', $facetOptionKey, 2);

		$explodedPath    = explode('/', $path);
		$lastPathSegment = $explodedPath[count($explodedPath) - 1];

		return $lastPathSegment;
	}

}