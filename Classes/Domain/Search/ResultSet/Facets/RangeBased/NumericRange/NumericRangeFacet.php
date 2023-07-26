<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\RangeBased\NumericRange;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\RangeBased\NumericRange\NumericRangeCount;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\RangeBased\NumericRange\NumericRangeFacet as SolrNumericRangeFacet;
use JsonSerializable;
use Netlogix\Nxsolrajax\Traits\FacetUrlTrait;

class NumericRangeFacet extends SolrNumericRangeFacet implements JsonSerializable
{
    use FacetUrlTrait;

    function jsonSerialize(): array
    {
        $range = $this->getRange();

        return [
            'name' => $this->getName(),
            'type' => self::TYPE_NUMERIC_RANGE,
            'label' => $this->getLabel(),
            'used' => $this->getIsUsed(),

            'count' => $range->getDocumentCount(),
            'selected' => $range->getSelected(),
            'rangeCounts' => array_map(
                fn (NumericRangeCount $numericRangeCount) => [
                    'count' => $numericRangeCount->getDocumentCount(),
                    'name' => $numericRangeCount->getRangeItem()
                ],
                $range->getRangeCounts()
            ),

            'step' => $range->getGap(),
            'min' => $range->getStartInResponse(),
            'max' => $range->getEndInResponse(),
            'minSelected' => $range->getStartRequested(),
            'maxSelected' => $range->getEndRequested(),

            'links' => [
                'self' => $this->getUrl(),
                'reset' => $this->getResetUrl()
            ]
        ];
    }

    public function getResetUrl(): string
    {
        return $this->getFacetResetUrl($this);
    }

    public function getUrl(): string
    {
        return $this->getFacetItemUrl($this->getRange(), '___FROM___-___TO___');
    }

}
