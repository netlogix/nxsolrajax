<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\RangeBased\DateRange;

use JsonSerializable;
use Netlogix\Nxsolrajax\Traits\FacetUrlTrait;

class DateRange extends
    \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRange implements JsonSerializable
{
    use FacetUrlTrait;

    function jsonSerialize(): array
    {
        return [
            'selected' => $this->getLabel() !== '',
            'start' => $this->getStartRequested() ? $this->getStartRequested()->getTimestamp() : '',
            'end' => $this->getEndRequested() ? $this->getEndRequested()->getTimestamp() : '',
            'min' => $this->getStartInResponse()->getTimestamp(),
            'max' => $this->getStartInResponse()->getTimestamp(),
            'links' => [
                'self' => $this->getUrl(),
            ]
        ];
    }

    public function getUrl(): string
    {
        return $this->getFacetItemUrl($this, '{dateRange}');
    }

}
