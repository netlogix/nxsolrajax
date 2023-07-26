<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\RangeBased\DateRange;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRange as SolrDateRange;
use JsonSerializable;
use Netlogix\Nxsolrajax\Traits\FacetUrlTrait;

class DateRange extends SolrDateRange implements JsonSerializable
{
    use FacetUrlTrait;

    function jsonSerialize(): array
    {
        return [
            'selected' => $this->getSelected(),
            'start' => $this->getStartRequested() ? $this->getStartRequested()?->getTimestamp() : '',
            'end' => $this->getEndRequested() ? $this->getEndRequested()?->getTimestamp() : '',
            'min' => $this->getStartInResponse()?->getTimestamp(),
            'max' => $this->getEndInResponse()?->getTimestamp(),
            'links' => [
                'self' => $this->getUrl(),
            ]
        ];
    }

    public function getUrl(): string
    {
        return $this->getFacetItemUrl($this, '___FROM___-___TO___');
    }

}
