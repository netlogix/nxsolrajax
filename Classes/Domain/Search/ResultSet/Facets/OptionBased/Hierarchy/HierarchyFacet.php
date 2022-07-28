<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy;

use JsonSerializable;
use Netlogix\Nxsolrajax\Traits\FacetUrlTrait;

class HierarchyFacet extends
    \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\HierarchyFacet implements
    JsonSerializable
{
    use FacetUrlTrait;

    function jsonSerialize(): array
    {
        return [
            'name' => $this->getName(),
            'type' => self::TYPE_HIERARCHY,
            'label' => $this->getLabel(),
            'used' => $this->getIsUsed(),
            'options' => array_values($this->getChildNodes()->getArrayCopy()),
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
