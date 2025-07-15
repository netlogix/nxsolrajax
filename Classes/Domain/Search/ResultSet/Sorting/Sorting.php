<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Sorting;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Sorting\Sorting as SolrSorting;
use JsonSerializable;
use Netlogix\Nxsolrajax\Traits\SearchUriBuilderTrait;

class Sorting extends SolrSorting implements JsonSerializable
{
    use SearchUriBuilderTrait;

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
        $previousRequest = $this->resultSet->getUsedSearchRequest();

        // This basically mimics the conditions from EXT:solr fluid partial
        if ($this->getIsResetOption()) {
            return $this->searchUriBuilder->getRemoveSortingUri($previousRequest);
        }
        return $this->searchUriBuilder->getSetSortingUri(
            previousSearchRequest: $previousRequest,
            sortingName: $this->getName(),
            sortingDirection: match ($this->getSelected()) {
                true => $this->getOppositeDirection(),
                false => $this->getDirection(),
            }
        );
    }
}
