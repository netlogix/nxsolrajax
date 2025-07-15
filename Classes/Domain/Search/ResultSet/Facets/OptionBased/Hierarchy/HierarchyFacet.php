<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\HierarchyFacet as SolrHierarchyFacet;
use JsonSerializable;
use Netlogix\Nxsolrajax\Traits\FacetUrlTrait;

class HierarchyFacet extends SolrHierarchyFacet implements JsonSerializable
{
    use FacetUrlTrait;

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->getName(),
            'type' => self::TYPE_HIERARCHY,
            'label' => $this->getLabel(),
            'used' => $this->getIsUsed(),
            'options' => $this->addSearchUriBuilderToOptions(array_values($this->getChildNodes()->getArrayCopy())),
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
