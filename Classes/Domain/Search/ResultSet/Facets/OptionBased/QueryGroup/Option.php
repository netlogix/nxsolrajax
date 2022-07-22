<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup;

use JsonSerializable;
use Netlogix\Nxsolrajax\Traits\FacetUrlTrait;

class Option extends \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\Option implements JsonSerializable
{
    use FacetUrlTrait;

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->getFacetItemUrl($this);
    }

    /**
     * @return array
     */
    function jsonSerialize()
    {
        return [
            'label' => $this->getLabel(),
            'name' => $this->getValue(),
            'count' => $this->getDocumentCount(),
            'selected' => $this->getSelected(),
            'links' => [
                'self' => $this->getUrl(),
            ]
        ];
    }
}
