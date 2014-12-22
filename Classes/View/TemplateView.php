<?php
namespace Netlogix\Nxsolrajax\View;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Sascha Nowak <sascha.nowak@netlogix.de>, netlogix media
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
 * A template engine to simplify the work with marker based templates. The
 * engine supports easy management of markers, subparts, and even loops.
 * @author Sascha Nowak <sascha.nowak@netlogix.de>
 * @package TYPO3
 * @subpackage solr
 */
class TemplateView extends \TYPO3\CMS\Fluid\View\StandaloneView implements \Tx_Solr_TemplateInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject
	 * @param string $templateName
	 */
	public function __construct(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject = NULL, $templateName = '') {
		parent::__construct($contentObject);

		$this->configurationManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
		if ($contentObject === NULL) {
			$contentObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		}
		$this->configurationManager->setContentObject($contentObject);

		$this->settings = $this->configurationManager->getConfiguration(
			\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, 'Solr'
		);

		$this->setLayoutRootPath(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->settings['view']['layoutRootPath']));
		$this->setPartialRootPath(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->settings['view']['partialRootPath']));

		$templateFilePath = $this->settings['view'][$templateName];
		if ($templateFilePath) {
			$this->setTemplatePathAndFilename(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($templateFilePath));
		}

	}

	/**
	 * @param string $actionName
	 *
	 * @return mixed
	 */
	public function render($actionName = NULL) {
		$this->assign('settings', $this->settings);
		return parent::render();
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function addVariable($key, $value) {
		$key = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToLowerCamelCase($key);
		$this->assign($key, $this->arrayKeysToLowerCamelCase($value));
	}

	/**
	 * @return \Netlogix\Nxsolrajax\View\TemplateView
	 */
	public function getTemplateClone() {
		$template = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('ApacheSolrForTypo3\\Solr\\View\\TemplateView', $this->configurationManager->getContentObject());
		return $template;
	}

	/**
	 * @param string $partialName
	 *
	 * @return string
	 */
	public function workOnSubpart($partialName) {
		$partialName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($partialName);
		$partialFilePath = $this->settings['view']['partialRootPath'] . $partialName . '.html';
		$this->setTemplatePathAndFilename(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($partialFilePath));
	}

	/**
	 * @param $subpartName
	 * @param $content
	 */
	public function addSubpart($subpartName, $content) {
		$subpartName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToLowerCamelCase($subpartName);
		$this->assign($subpartName, $content);
	}

	/**
	 * @param string $loopName
	 * @param string $markerName
	 * @param array $variables
	 */
	public function addLoop($loopName, $markerName, array $variables) {
		$loopName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToLowerCamelCase($loopName);
		$this->assign($loopName, $this->arrayKeysToLowerCamelCase($variables));
	}

	/**
	 * @param mixed $array
	 *
	 * @return array
	 */
	protected function arrayKeysToLowerCamelCase($array) {
		if (!is_array($array)) {
			return $array;
		}

		$lowerCamelCaseArray = array();
		foreach($array as $key => $value) {
			if (preg_match('/loop_(.*)\|/', $key, $matches)) {
				$key = $matches[1];
			}
			$lowerCamelCaseArray[\TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToLowerCamelCase($key)] = is_array($value) ? $this->arrayKeysToLowerCamelCase($value) : $value;
		}
		return $lowerCamelCaseArray;
	}

}

?>