<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy;

use JsonSerializable;
use Netlogix\Nxsolrajax\Traits\FacetUrlTrait;

class Node extends \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\Node implements JsonSerializable
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
     * @return bool
     */
    public function isActive()
    {
        if ($this->getSelected()) {
            return true;
        }
        /** @var Node $childNode */
        foreach ($this->childNodes as $childNode) {
            if ($childNode->isActive()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array
     */
    function jsonSerialize()
    {
        return [
            'id' => $this->getKey(),
            'label' => $this->getLabel(),
            'name' => $this->getValue(),
            'count' => $this->getDocumentCount(),
            'selected' => $this->getSelected(),
            'active' => $this->isActive(),
            'options' => array_values($this->childNodes->getArrayCopy()),
            'links' => [
                'self' => $this->getUrl(),
            ]
        ];
    }

}
