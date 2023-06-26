<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Sorting;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Sorting\Sorting as SolrSorting;
use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use JsonSerializable;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Sorting extends SolrSorting implements JsonSerializable
{

    public function jsonSerialize(): array
    {
        return [
            'label' => $this->getLabel(),
            'url' => $this->getUrl(),
            'direction' => $this->getDirection(),
            'resetOption' => $this->getIsResetOption(),
            'selected' => $this->getSelected(),
        ];
    }

    public function getUrl(): string
    {
        $searchUriBuilder = GeneralUtility::makeInstance(SearchUriBuilder::class);
        $previousRequest = $this->resultSet->getUsedSearchRequest();

        // This basically mimics the conditions from EXT:solr fluid partial
        if ($this->getIsResetOption() === true) {
            return $searchUriBuilder->getRemoveSortingUri($previousRequest);
        } else {
            return $searchUriBuilder->getSetSortingUri(
                previousSearchRequest: $previousRequest,
                sortingName: $this->getName(),
                sortingDirection: match ($this->getSelected()) {
                    true => $this->getOppositeDirection(),
                    false => $this->getDirection(),
                }
            );
        }
    }
}
