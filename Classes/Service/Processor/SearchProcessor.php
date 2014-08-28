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
class SearchProcessor implements \Netlogix\Nxsolrajax\Service\Processor\ProcessorInterface {

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

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
	 * @param array $result
	 *
	 * @return array
	 */
	public function processResult($result = array()) {

		$result['q'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('q');
		$result['suggestUrl'] = $this->getSuggestEidUrl();

		return $result;
	}

	/**
	 * Returns the eID URL for the AJAX suggestion request.
	 *
	 * @author Mario Rimann <mario.rimann@internezzo.ch>
	 * @return string the full URL to the eID script including the needed parameters
	 */
	protected function getSuggestEidUrl() {
		$suggestUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL');

		if ($this->settings['suggest']['forceHttps']) {
			$suggestUrl = str_replace('http://', 'https://', $suggestUrl);
		}

		$params = array(
			'eID' => 'tx_nxsolrajax_suggest',
			'id' => $GLOBALS['TSFE']->id,
			'L' => $GLOBALS['TSFE']->sys_language_uid,
		);

		$getParams = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('tx_solr');
		if ($this->settings['suggest']['siteSelector'] && isset($getParams['site'])) {
			$params['tx_solr']['site'] = $getParams['site'];
		}

		if (is_array($this->settings['suggest']['filter']) && isset($getParams['filter'])) {
			foreach($getParams['filter'] as $filter) {
				$filter = urldecode($filter);
				list($filterName) = explode(':', $filter);
				if (array_key_exists($filterName, $this->settings['suggest']['filter'])) {
					$params['tx_solr']['filter'][] = $filter;
				}
			}
		}

		$suggestUrl .= '?' . http_build_query($params) ;

		return $suggestUrl;
	}

}