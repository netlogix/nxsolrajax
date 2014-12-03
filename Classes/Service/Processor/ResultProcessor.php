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
class ResultProcessor implements \Netlogix\Nxsolrajax\Service\Processor\ProcessorInterface {

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
	 * @param \Tx_Solr_Search $search
	 */
	public function injectSearch(\Tx_Solr_Search $search) {
		$this->search = $search;
	}

	/**
	 * @return array
	 */
	public function processResult($result = array()) {

		$query = $this->search->getQuery();

		$result['query'] = $query->getKeywordsCleaned();
		$result['limit'] = $query->getResultsPerPage();
		$result['count'] = $this->search->getNumberOfResults();
		$result['offset'] = $this->search->getResultOffset();

		$resultClassNameMapping = $this->settings['search']['results']['resultClassNameMapping'];
		$resultMappingTypeField = $this->settings['search']['results']['resultMappingTypeField'] ?: 'type';

		$responseDocuments = $this->getResultDocuments();

		foreach($responseDocuments as $responseDocument) {
			$type = $responseDocument[$resultMappingTypeField];
			if (array_key_exists($type, $resultClassNameMapping)) {
				$dtoClassName = $resultClassNameMapping[$type];
			} else {
				$dtoClassName = $resultClassNameMapping['defaultResult'];
			}

			$result['resultDocuments'][] = $this->objectManager->get($dtoClassName, $this->renderDocumentFields($responseDocument));
		}

		if (!$this->search->getNumberOfResults()) {
			$result = array_merge($result, $this->getSuggestion());
		}

		return $result;
	}

	/**
	 * Query URL with a suggested/corrected query
	 *
	 * @return string Suggestion/spellchecked query URL
	 */
	public function getSuggestion() {
		/** @var \Tx_Solr_SpellChecker $spellChecker */
		$spellChecker = $this->objectManager->get('Tx_Solr_SpellChecker');
		$suggestions = $spellChecker->getSuggestions();

		$query = clone $this->search->getQuery();
		$query->setKeywords($suggestions['collation']);

		/** @var \Tx_Solr_Query_LinkBuilder $queryLinkBuilder */
		$queryLinkBuilder = $this->objectManager->get('Tx_Solr_Query_LinkBuilder', $query);
		$queryLinkBuilder->setLinkTargetPageId($GLOBALS['TSFE']->id);

		$result = array(
			'suggestion' => $suggestions['collation'],
			'suggestionUrl' => htmlspecialchars_decode($queryLinkBuilder->getQueryUrl(array('isAjax' => 1)))
		);

		return $result;
	}

	/**
	 * @return $this
	 */
	public function getParentPlugin() {
		return $this;
	}

	/**
	 * @return \Tx_Solr_Search
	 */
	public function getSearch() {
		return $this->search;
	}

	/**
	 * @return array
	 */
	protected function getResultDocuments() {
		$responseDocuments = $this->search->getResultDocuments();
		$resultDocuments = array();

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['modifyResultSet'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['modifyResultSet'] as $classReference) {
				$resultSetModifier = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classReference);

				if ($resultSetModifier instanceof \Tx_Solr_ResultSetModifier) {
					$responseDocuments = $resultSetModifier->modifyResultSet($this, $responseDocuments);
				} else {
					throw new \UnexpectedValueException(
						get_class($resultSetModifier) . ' must implement interface Tx_Solr_ResultSetModifier',
						1310386927
					);
				}
			}
		}

		foreach ($responseDocuments as $resultDocument) {
			$temporaryResultDocument = $this->processDocumentFieldsToArray($resultDocument);

			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['modifyResultDocument'])) {
				foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['modifyResultDocument'] as $classReference) {
					$resultDocumentModifier = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classReference);

					if ($resultDocumentModifier instanceof \Tx_Solr_ResultDocumentModifier) {
						$temporaryResultDocument = $resultDocumentModifier->modifyResultDocument($this, $temporaryResultDocument);
					} else {
						throw new \UnexpectedValueException(
							get_class($resultDocumentModifier) . ' must implement interface Tx_Solr_ResultDocumentModifier',
							1310386725
						);
					}
				}
			}

			$resultDocuments[] = $temporaryResultDocument;
			unset($temporaryResultDocument);
		}

		return $resultDocuments;
	}

	/**
	 * takes a search result document and processes its fields according to the
	 * instructions configured in TS. Currently available instructions are
	 *    * timestamp - converts a date field into a unix timestamp
	 *    * serialize - uses serialize() to encode multivalue fields which then can be put out using the MULTIVALUE view helper
	 *    * skip - skips the whole field so that it is not available in the result, usefull for the spell field f.e.
	 * The default is to do nothing and just add the document's field to the
	 * resulting array.
	 *
	 * @param    \Apache_Solr_Document $document the Apache_Solr_Document result document
	 *
	 * @return    array    An array with field values processed like defined in TS
	 */
	protected function processDocumentFieldsToArray(\Apache_Solr_Document $document) {
		$processingInstructions = $this->settings['search']['results']['fieldProcessingInstructions'];
		$availableFields = $document->getFieldNames();
		$result = array();

		foreach ($availableFields as $fieldName) {
			$processingInstruction = $processingInstructions[$fieldName];

			switch ($processingInstruction) {
				case 'timestamp':
					$processedFieldValue = \Tx_Solr_Util::isoToTimestamp($document->{$fieldName});
					break;
				case 'serialize':
					if (!empty($document->{$fieldName})) {
						$processedFieldValue = serialize($document->{$fieldName});
					} else {
						$processedFieldValue = '';
					}
					break;
				case 'skip':
					continue 2;
				default:
					$processedFieldValue = $document->{$fieldName};
			}

			$result[$fieldName] = $processedFieldValue;
		}

		return $result;
	}

	protected function renderDocumentFields(array $document) {
		$renderingInstructions = $this->settings['search']['results']['fieldRenderingInstructions'];
		/** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj */
		$cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		$cObj->start($document);

		foreach ($renderingInstructions as $renderingInstructionName => $renderingInstruction) {
			if (!is_array($renderingInstruction)) {
				$renderedField = $cObj->cObjGetSingle(
					$renderingInstructions[$renderingInstructionName],
					$renderingInstructions[$renderingInstructionName . '.']
				);

				$document[$renderingInstructionName] = $renderedField;
			}
		}

		return $document;
	}

}