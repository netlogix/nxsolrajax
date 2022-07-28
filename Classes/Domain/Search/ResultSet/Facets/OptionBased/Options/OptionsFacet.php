<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Options;

use JsonSerializable;
use Netlogix\Nxsolrajax\Traits\FacetUrlTrait;

class OptionsFacet extends
    \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Options\OptionsFacet implements JsonSerializable
{
    use FacetUrlTrait;

    function jsonSerialize(): array
    {
        return [
            'name' => $this->getName(),
            'type' => self::TYPE_OPTIONS,
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
