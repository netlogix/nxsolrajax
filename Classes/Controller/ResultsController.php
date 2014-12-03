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
class ResultsController extends \Tx_Solr_PiResults_Results {

	/**
	 * Performs post initialization.
	 *
	 * @see Tx_Solr_PluginBase_PluginBase#postInitialize()
	 */
	protected function postInitialize() {
		// Enable cache
	}

	/**
	 * This method executes the requested commands and applies the changes to
	 * the template.
	 *
	 * @param $actionResult
	 * @throws \RuntimeException
	 * @throws \UnexpectedValueException
	 * @return string Rendered plugin content
	 */
	protected function render($actionResult) {
		$allCommands = \Tx_Solr_CommandResolver::getAllPluginCommandsList();
		$commandList = $this->getCommandList();

		// render commands matching the plugin's requirements
		foreach ($commandList as $commandName) {
			$GLOBALS['TT']->push('solr-' . $commandName);

			$commandContent   = '';
			$commandVariables = $this->executeCommand($commandName);
			if (!is_null($commandVariables)) {
				if ($this->conf['alternativeTemplateEngine']) {
					$this->template->addVariable($commandName, $commandVariables);
				} else {
					$commandContent = $this->renderCommand($commandName, $commandVariables);
					$this->template->addSubpart('solr_search_' . $commandName, $commandContent);
				}
			}

			$this->template->addSubpart('solr_search_' . $commandName, $commandContent);
			unset($subpartTemplate);
			$GLOBALS['TT']->pull($commandContent);
		}

		// remove subparts for commands that are registered but not matching the requirements
		$nonMatchingCommands = array_diff($allCommands, $commandList);
		foreach ($nonMatchingCommands as $nonMatchingCommand) {
			$this->template->addSubpart('solr_search_' . $nonMatchingCommand, '');
		}

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr'][$this->getPluginKey()]['renderTemplate'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr'][$this->getPluginKey()]['renderTemplate'] as $classReference) {
				$templateModifier = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classReference);

				if ($templateModifier instanceof \Tx_Solr_TemplateModifier) {
					$templateModifier->modifyTemplate($this->template);
				} else {
					throw new \UnexpectedValueException(
						get_class($templateModifier) . ' must implement interface Tx_Solr_TemplateModifier',
						1310387230
					);
				}
			}
		}

		$this->javascriptManager->addJavascriptToPage();

		return $this->template->render(\Tx_Solr_Template::CLEAN_TEMPLATE_YES);
	}

	/**
	 * Initializes the template engine and returns the initialized instance.
	 *
	 * @return \Tx_Solr_TemplateInterface
	 */
	protected function initializeTemplateEngine() {
		$templateFile = $this->getTemplateFile();
		$subPart      = $this->getSubpart();

		$flexformTemplateFile = $this->pi_getFFvalue(
			$this->cObj->data['pi_flexform'],
			'templateFile',
			'sOptions'
		);
		if (!empty($flexformTemplateFile)) {
			$templateFile = $flexformTemplateFile;
		}

		if ($this->conf['alternativeTemplateEngine']) {
			$template = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($this->conf['alternativeTemplateEngineClassName'], $this->cObj, $this->getTemplateFileKey());
			if (!$template instanceof \Tx_Solr_TemplateInterface) {
				throw new \UnexpectedValueException(
					get_class($template) . ' must implement interface Tx_Solr_TemplateInterface',
					1393769844
				);
			}
		} else {
			$template = \t3lib_div::makeInstance(
				'Tx_Solr_Template',
				$this->cObj,
				$templateFile,
				$subPart
			);
			$template->addViewHelperIncludePath($this->extKey, 'Classes/ViewHelper/');
			$template->addViewHelper('LLL', array(
				'languageFile' => $GLOBALS['PATH_solr'] .'Resources/Private/Language/' . str_replace('Pi', 'Plugin', $this->getPluginKey()) . '.xml',
				'llKey'        => $this->LLkey
			));

				// can be used for view helpers that need configuration during initialization
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr'][$this->getPluginKey()]['addViewHelpers'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr'][$this->getPluginKey()]['addViewHelpers'] as $classReference) {
					$viewHelperProvider = &\t3lib_div::getUserObj($classReference);

					if ($viewHelperProvider instanceof \Tx_Solr_ViewHelperProvider) {
						$viewHelpers = $viewHelperProvider->getViewHelpers();
						foreach ($viewHelpers as $helperName => $helperObject) {
							$helperAdded = $template->addViewHelperObject($helperName, $helperObject);
								// TODO check whether $helperAdded is TRUE, throw an exception if not
						}
					} else {
						throw new \UnexpectedValueException(
							get_class($viewHelperProvider) . ' must implement interface Tx_Solr_ViewHelperProvider',
							1310387296
						);
					}
				}
			}
		}

		$template = $this->postInitializeTemplateEngine($template);

		$this->template = $template;
	}

}