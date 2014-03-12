<?php
namespace Netlogix\Nxsolrajax\View;

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
class RawDataView implements \Tx_Solr_TemplateInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var array
	 */
	protected $content;

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManage
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManage) {
		$this->objectManager = $objectManage;
	}

	/**
	 * @param string $actionName
	 *
	 * @return mixed
	 */
	public function render($actionName = NULL) {
		return $this->content;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function addVariable($key, $value) {
		$key = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToLowerCamelCase($key);
		$this->content[$key] = $this->arrayKeysToLowerCamelCase($value);
	}

	/**
	 * @return \Tx_Solr_TemplateInterface
	 */
	public function getTemplateClone() {
		return $this->objectManager->get('Netlogix\\Nxsolrajax\\View\\RawDataView');
	}

	/**
	 * @param string $subpartName
	 *
	 * @return string
	 */
	public function workOnSubpart($subpartName) {

	}

	/**
	 * @param $subpartName
	 * @param $content
	 */
	public function addSubpart($subpartName, $content) {
		$subpartName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToLowerCamelCase($subpartName);
		$this->content[$subpartName] = $this->arrayKeysToLowerCamelCase($content);
	}

	/**
	 * @param string $loopName
	 * @param string $markerName
	 * @param array $variables
	 */
	public function addLoop($loopName, $markerName, array $variables) {
		$loopName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToLowerCamelCase($loopName);
		$this->content[$loopName] = $this->arrayKeysToLowerCamelCase($variables);
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

	/**
	 * @return \Netlogix\Nxsolrajax\View\RawDataView
	 */
	function __clone() {
		return $this->objectManager->get('Netlogix\\Nxsolrajax\\View\\RawDataView');
	}

}