<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Sorting;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use JsonSerializable;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class Sorting extends \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Sorting\Sorting implements JsonSerializable
{

    protected SearchUriBuilder $searchUriBuilder;

    protected SearchResultSet $resultSet;

    /**
     * @inheritdoc
     */
    public function __construct(
        SearchResultSet $resultSet,
        $name,
        $field,
        $direction,
        $label,
        $selected,
        $isResetOption
    ) {
        parent::__construct($resultSet, $name, $field, $direction, $label, $selected, $isResetOption);
        $this->resultSet = $resultSet;
        $this->searchUriBuilder = GeneralUtility::makeInstance(ObjectManager::class)->get(SearchUriBuilder::class);
    }

    public function jsonSerialize(): array
    {
        return [
            'label' => $this->getLabel(),
            'url' => $this->getUrl(),
            'direction' => $this->getDirection(),
            'resetOption' => !!$this->getIsResetOption(),
            'selected' => !!$this->getSelected(),
        ];
    }

    public function getUrl(): string
    {
        $previousRequest = $this->resultSet->getUsedSearchRequest();

        $reset = $this->getIsResetOption();
        $selected = $this->getSelected();

        // This basically mimics the conditions from EXT:solr fluid partial
        if ($reset) {
            return $this->searchUriBuilder->getRemoveSortingUri($previousRequest);
        } elseif (!$reset && $selected) {
            return $this->searchUriBuilder->getSetSortingUri(
                $previousRequest,
                $this->getName(),
                $this->getOppositeDirection()
            );
        } elseif (!$reset && !$selected) {
            return $this->searchUriBuilder->getSetSortingUri($previousRequest, $this->getName(), $this->getDirection());
        }

        return '';
    }
}
