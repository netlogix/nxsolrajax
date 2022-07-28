<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\RangeBased\DateRange;

use JsonSerializable;
use Netlogix\Nxsolrajax\Traits\FacetUrlTrait;

class DateRangeFacet extends
    \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\RangeBased\DateRange\DateRangeFacet implements
    JsonSerializable
{
    use FacetUrlTrait;

    function jsonSerialize(): array
    {
        return [
            'name' => $this->getName(),
            'type' => self::TYPE_DATE_RANGE,
            'label' => $this->getLabel(),
            'used' => $this->getIsUsed(),
            'options' => $this->getRange(),
            'links' => [
                'reset' => $this->getResetUrl()
            ]
        ];
    }

    public function getResetUrl(): string
    {
        return $this->getFacetResetUrl($this);
    }

}
