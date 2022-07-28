<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup;

use JsonSerializable;
use Netlogix\Nxsolrajax\Traits\FacetUrlTrait;

class QueryGroupFacet extends
    \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\QueryGroupFacet implements
    JsonSerializable
{
    use FacetUrlTrait;

    function jsonSerialize(): array
    {
        return [
            'name' => $this->getName(),
            'type' => self::TYPE_QUERY_GROUP,
            'label' => $this->getLabel(),
            'used' => $this->getIsUsed(),
            'options' => array_values($this->getOptions()->getArrayCopy()),
            'links' => [
                'reset' => $this->getResetUrl(),
            ]
        ];
    }

    public function getResetUrl(): string
    {
        return $this->getFacetResetUrl($this);
    }

}
