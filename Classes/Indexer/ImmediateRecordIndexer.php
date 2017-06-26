<?php
namespace Netlogix\Nxsolrajax\Indexer;

/***************************************************************
*  Copyright notice
*
*  (c) 2014 Sascha Nowak <sascha.nowak@netlogix.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
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
 * Indexes records when they are saved
 *
 * @author Sascha Nowak <sascha.nowak@netlogix.de>
 * @package TYPO3
 * @subpackage nxnetzschsolr
 */
class ImmediateRecordIndexer {

	/**
	 * @var \Tx_Solr_IndexQueue_Queue
	 */
	protected $indexQueue;

	/**
	 * Configuration of solr extension
	 *
	 * @var array
	 */
	protected $configuration;

	/**
	 * constructor
	 */
	public function __construct() {
		$this->indexQueue = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_IndexQueue_Queue');
	}

	/**
	 * @param string $status
	 * @param string $table
	 * @param string $uid
	 * @param array $fields
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
	 */
	public function processDatamap_afterDatabaseOperations(&$status, &$table, &$uid, array &$fields, \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler) {

		$recordTable = $table;
		$recordUid = $uid;
		$recordPageId = 0;

		if ($status == 'new') {
			$recordUid = $dataHandler->substNEWwithIDs[$recordUid];
		}

		if (\Tx_Solr_Util::isDraftRecord($recordTable, $recordUid)) {
			// skip workspaces: index only LIVE workspace
			return;
		}

		if ($status == 'update' && !isset($fields['pid'])) {
			$recordPageId = $dataHandler->getPID($recordTable, $recordUid);
		} else {
			$recordPageId = $fields['pid'];
		}

		// when a content element changes we need to updated the page instead
		if ($recordTable == 'tt_content') {
			$recordTable = 'pages';
			$recordUid = $recordPageId;
		} elseif ($recordTable == 'pages_language_overlay') {
			$recordTable = 'pages';
		}

		$this->configuration = \Tx_Solr_Util::getSolrConfigurationFromPageId($recordPageId);
		$monitoredTables = $this->getMonitoredTables($recordPageId);

		if (in_array($recordTable, $monitoredTables, TRUE)) {

			$record = $this->getRecord($recordTable, $recordUid);

			if (!empty($record)) {
				// only update/insert the item if we actually found a record

				if ($this->isLocalizedRecord($recordTable, $record)) {
					// if it's a localization overlay, update the original record instead
					$recordUid = $record[$GLOBALS['TCA'][$recordTable]['ctrl']['transOrigPointerField']];
				}

				/** @var \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer */
				$pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Page\\PageRenderer');
				$store = [];
				foreach (['backPath', 'templateFile'] as $key) {
					$store[$key] = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($pageRenderer, $key);
				}

				$items = $this->indexQueue->getItems($recordTable, $recordUid);
				/** @var \Tx_Solr_IndexQueue_Item $item */
				foreach ($items as $item) {
					try {
						$indexer = $this->getIndexerByItem($item->getIndexingConfigurationName());

						$itemIndexed = $indexer->index($item);

						// update IQ item so that the IQ can determine what's been indexed already
						if ($itemIndexed) {
							$item->updateIndexedTime();
						}
					} catch (\Exception $e) {
						$this->indexQueue->markItemAsFailed($item, $e->getCode() . ': ' . $e->__toString());
					}
				}
				foreach (array_keys($store) as $key) {
					\TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($pageRenderer, $key, $store[$key]);
				}
			}
		}

	}

	/**
	 * Gets an array of tables configured for indexing by the Index Queue. The
	 * record monitor must watch these tables for manipulation.
	 *
	 * @return array Array of table names to be watched by the record monitor.
	 */
	protected function getMonitoredTables() {
		$monitoredTables = array();

		$indexingConfigurations = $this->indexQueue->getTableIndexingConfigurations($this->configuration);

		foreach ($indexingConfigurations as $indexingConfigurationName) {
			$monitoredTable = $indexingConfigurationName;

			if (!empty($this->configuration['index.']['queue.'][$indexingConfigurationName . '.']['table'])) {
					// table has been set explicitly. Allows to index the same table with different configurations
				$monitoredTable = $this->configuration['index.']['queue.'][$indexingConfigurationName . '.']['table'];
			}

			if ($monitoredTable !== 'pages') {
				$monitoredTables[] = $monitoredTable;
			}
		}

		return array_unique($monitoredTables);
	}

	/**
	 * A factory method to get an indexer depending on an item's configuration.
	 *
	 * By default all items are indexed using the default indexer
	 * (Tx_Solr_IndexQueue_Indexer) coming with EXT:solr. Pages by default are
	 * configured to be indexed through a dedicated indexer
	 * (Tx_Solr_IndexQueue_PageIndexer). In all other cases a dedicated indexer
	 * can be specified through TypoScript if needed.
	 *
	 * @param string $indexingConfigurationName Indexing configuration name.
	 * @return \Tx_Solr_IndexQueue_Indexer An instance of Tx_Solr_IndexQueue_Indexer or a sub class of it.
	 * @throws \RuntimeException
	 */
	protected function getIndexerByItem($indexingConfigurationName) {
		$indexerClass = 'Tx_Solr_IndexQueue_Indexer';
		$indexerOptions = array();

		// allow to overwrite indexers per indexing configuration
		if (isset($this->configuration['index.']['queue.'][$indexingConfigurationName . '.']['indexer'])) {
			$indexerClass = $this->configuration['index.']['queue.'][$indexingConfigurationName . '.']['indexer'];
		}

		// get indexer options
		if (isset($this->configuration['index.']['queue.'][$indexingConfigurationName . '.']['indexer.'])
				&& !empty($this->configuration['index.']['queue.'][$indexingConfigurationName . '.']['indexer.'])
		) {
			$indexerOptions = $this->configuration['index.']['queue.'][$indexingConfigurationName . '.']['indexer.'];
		}

		$indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($indexerClass, $indexerOptions);
		if (!($indexer instanceof \Tx_Solr_IndexQueue_Indexer)) {
			throw new \RuntimeException(
					'The indexer class "' . $indexerClass . '" for indexing configuration "' . $indexingConfigurationName . '" is not a valid indexer. Must be a subclass of Tx_Solr_IndexQueue_Indexer.',
					1260463206
			);
		}

		return $indexer;
	}

	/**
	 * Retrieves a record, taking into account the additionalWhereClauses of the
	 * Indexing Queue configurations.
	 *
	 * @param string $recordTable Table to read from
	 * @param int $recordUid Id of the record
	 * @return array Record if found, otherwise empty array
	 */
	protected function getRecord($recordTable, $recordUid) {
		$record = array();

		$indexingConfigurations = $this->indexQueue->getTableIndexingConfigurations($this->configuration);

		foreach ($indexingConfigurations as $indexingConfigurationName) {
			$tableToIndex = $indexingConfigurationName;
			if (!empty($this->configuration['index.']['queue.'][$indexingConfigurationName . '.']['table'])) {
				// table has been set explicitly. Allows to index the same table with different configurations
				$tableToIndex = $this->configuration['index.']['queue.'][$indexingConfigurationName . '.']['table'];
			}

			if ($tableToIndex === $recordTable) {
				$recordWhereClause = $this->buildUserWhereClause($indexingConfigurationName);
				$record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($recordTable, $recordUid, '*', $recordWhereClause);

				if (!empty($record)) {
					// if we found a record which matches the conditions, we can continue
					break;
				}
			}
		}

		return $record;
	}

	/**
	 * Build additional where clause from index queue configuration
	 *
	 * @param string $indexingConfigurationName Indexing configuration name
	 * @return string Optional extra where clause
	 */
	protected function buildUserWhereClause($indexingConfigurationName) {
		$condition = '';

		// FIXME replace this with the mechanism described in Tx_Solr_IndexQueue_Initializer_Abstract::buildUserWhereClause()
		if (isset($this->configuration['index.']['queue.'][$indexingConfigurationName . '.']['additionalWhereClause'])) {
			$condition = ' AND ' . $this->configuration['index.']['queue.'][$indexingConfigurationName . '.']['additionalWhereClause'];
		}

		return $condition;
	}

	/**
	 * Checks whether a record is a localization overlay.
	 *
	 * @param string $table The record's table name
	 * @param array $record The record to check
	 * @return boolean TRUE if the record is a language overlay, FALSE otherwise
	 */
	protected function isLocalizedRecord($table, array $record) {
		$isLocalizedRecord = FALSE;

		if (isset($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'])) {
			$translationOriginalPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];

			if ($record[$translationOriginalPointerField] > 0) {
				$isLocalizedRecord = TRUE;
			}
		}

		return $isLocalizedRecord;
	}
}
