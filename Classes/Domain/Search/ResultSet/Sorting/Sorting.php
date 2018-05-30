<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Sorting;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

class Sorting extends \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Sorting\Sorting implements \JsonSerializable
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var SearchUriBuilder
     */
    protected $searchUriBuilder;

    /**
     * @var SearchResultSet
     */
    protected $resultSet;

    /**
     * @inheritdoc
     */
    public function __construct(SearchResultSet $resultSet, $name, $field, $direction, $label, $selected, $isResetOption)
    {
        parent::__construct($resultSet, $name, $field, $direction, $label, $selected, $isResetOption);
        $this->resultSet = $resultSet;
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->searchUriBuilder = $this->objectManager->get(SearchUriBuilder::class);
    }

    public function jsonSerialize()
    {
        $result = [
            'label' => $this->getLabel(),
            'url' => $this->getUrl(),
            'direction' => $this->getDirection(),
            'resetOption' => !!$this->getIsResetOption(),
            'selected' => !!$this->getSelected(),
        ];

        return $result;
    }

    public function getUrl()
    {
        $previousRequest = $this->resultSet->getUsedSearchRequest();

        $reset = $this->getIsResetOption();
        $selected = $this->getSelected();

        // This basically mimics the conditions from EXT:solr fluid partial
        if ($reset) {
            return $this->searchUriBuilder->getRemoveSortingUri($previousRequest);
        } elseif (!$reset && $selected) {
            return $this->searchUriBuilder->getSetSortingUri($previousRequest, $this->getName(), $this->getOppositeDirection());
        } elseif (!$reset && !$selected) {
            return $this->searchUriBuilder->getSetSortingUri($previousRequest, $this->getName(), $this->getDirection());
        }
    }
}