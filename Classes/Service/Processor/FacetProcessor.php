<?php
namespace Netlogix\Nxsolrajax\Service\Processor;

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
class FacetProcessor implements \Netlogix\Nxsolrajax\Service\Processor\ProcessorInterface {

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\TypoScriptService
	 */
	protected $typoScriptService;

	/**
	 * @var \Tx_Solr_Search
	 */
	protected $search;

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManage
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManage) {
		$this->objectManager = $objectManage;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager) {
		$configurationManager->setConfiguration(array('extensionName' => 'solr'));
		$this->settings = $configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'solr');
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Service\TypoScriptService $typoScriptService
	 */
	public function injectTypoScriptService(\TYPO3\CMS\Extbase\Service\TypoScriptService $typoScriptService) {
		$this->typoScriptService = $typoScriptService;
	}

	/**
	 * @param \Tx_Solr_Search $search
	 */
	public function injectSearch(\Tx_Solr_Search $search) {
		$this->search = $search;
	}

	/**
	 * @return \Netlogix\Nxsolrajax\Domain\Model\Dto\Facet
	 */
	public function processResult() {
		$facetResult = array();

		if ($this->settings['search']['faceting']
			&& ($this->search->getNumberOfResults() || $this->settings['search']['initializeWithEmptyQuery'] || $this->settings['search']['initializeWithQuery'] || $this->settings['search']['alwaysShowFacets'])
		) {
			$facetResult['availableFacets'] = $this->renderAvailableFacets();
			$facetResult['usedFacets'] = $this->renderUsedFacets();
		}

		return $this->objectManager->get('Netlogix\\Nxsolrajax\\Domain\\Model\\Dto\\Facet', $facetResult);
	}

	/**
	 * @return array
	 */
	protected function renderAvailableFacets() {

		/** @var \Netlogix\Nxsolrajax\View\RawDataView $template */
		$template = $this->objectManager->get('Netlogix\\Nxsolrajax\\View\\RawDataView');
		$configuredFacets = $this->settings['search']['faceting']['facets'];

		/** @var \Tx_Solr_Facet_FacetRendererFactory $facetRendererFactory */
		$facetRendererFactory = $this->objectManager->get('Tx_Solr_Facet_FacetRendererFactory', $this->typoScriptService->convertPlainArrayToTypoScriptArray($configuredFacets));

		$facetContent = array();
		foreach ($configuredFacets as $facetName => $facetConfiguration) {

			/** @var \Tx_Solr_Facet_Facet $facet */
			$facet =  $this->objectManager->get('Tx_Solr_Facet_Facet', $facetName, $facetRendererFactory->getFacetInternalType($facetName));

			if (
				(isset($facetConfiguration['includeInAvailableFacets']) && $facetConfiguration['includeInAvailableFacets'] == '0')
				|| !$facet->isRenderingAllowed() || $facet->getOptionsCount() == 0
			) {
					// don't render facets that should not be included in available facets
					// or that do not meet their requirements to be rendered
				continue;
			}

			$facetRenderer = $facetRendererFactory->getFacetRendererByFacet($facet);
			$facetRenderer->setTemplate($template);
			$facetRenderer->setLinkTargetPageId($this->settings['search']['targetPage']);

			if ($facet->isActive()) {
				$this->facetsActive = TRUE;
			}
			$facetResult = $facetRenderer->renderFacet();
			$facetResult['type'] = $facetConfiguration['type'] ?: 'default';
			$facetResult['name'] = $facetName;

			$facetContent[] = $facetResult;
		}

		return $facetContent;
	}

	/**
	 * @return array
	 */
	protected function renderUsedFacets() {

		/** @var \Netlogix\Nxsolrajax\View\RawDataView $template */
		$template = $this->objectManager->get('Netlogix\\Nxsolrajax\\View\\RawDataView');

		$query = $this->search->getQuery();

		$queryLinkBuilder = $this->objectManager->get('Tx_Solr_Query_LinkBuilder', $this->search->getQuery());
		/* @var $queryLinkBuilder \Tx_Solr_Query_LinkBuilder */
		$queryLinkBuilder->setLinkTargetPageId($this->settings['search']['targetPage']);

		// URL parameters added to facet URLs may not need to be added to the facets reset URL
		if (!empty($this->settings['search']['faceting']['facetLinkUrlParameters'])
		&& isset($this->settings['search']['faceting']['facetLinkUrlParameters']['useForFacetResetLinkUrl'])
		&& $this->settings['search']['faceting']['facetLinkUrlParameters']['useForFacetResetLinkUrl'] === '0') {
			$addedUrlParameters = \TYPO3\CMS\Core\Utility\GeneralUtility::explodeUrl2Array($this->settings['search']['faceting']['facetLinkUrlParameters']);
			$addedUrlParameterKeys = array_keys($addedUrlParameters);

			foreach ($addedUrlParameterKeys as $addedUrlParameterKey) {
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($addedUrlParameterKey, 'tx_solr')) {
					$addedUrlParameterKey = substr($addedUrlParameterKey, 8, -1);
					$queryLinkBuilder->addUnwantedUrlParameter($addedUrlParameterKey);
				}
			}
		}

		$resultParameters = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('tx_solr');
		$filterParameters = array();
		if (isset($resultParameters['filter'])) {
			$filterParameters = (array) array_map('urldecode', $resultParameters['filter']);
		}

		$facetsInUse = array();
		foreach ($filterParameters as $filter) {
			// only split by the first ":" to allow the use of colons in the filter value
			list($filterName, $filterValue) = explode(':', $filter, 2);

			$facetConfiguration = $this->settings['search']['faceting']['facets'][$filterName];

			// don't render facets that should not be included in used facets
			if (isset($facetConfiguration['includeInUsedFacets']) && $facetConfiguration['includeInUsedFacets'] == '0') {
				continue;
			}

			/** @var \Tx_Solr_Facet_UsedFacetRenderer $usedFacetRenderer */
			$usedFacetRenderer = $this->objectManager->get('Tx_Solr_Facet_UsedFacetRenderer', $filterName, $filterValue, $filter, $template, $query);
			$usedFacetRenderer->setLinkTargetPageId($this->settings['search']['targetPage']);

			$facetToRemove = $usedFacetRenderer->render();

			$facetsInUse[] = $facetToRemove;
		}

		$template->addVariable('remove_facet', $facetsInUse);
		$template->addVariable('remove_all_facets', array('url' => $queryLinkBuilder->getQueryUrl(array('filter' => array(), 'isAjax' => 1)), 'text' => 'solr.facet.showAll'));

		$content = array();
		if (count($facetsInUse)) {
			$content = $template->render();
		}

		return $content;
	}

}