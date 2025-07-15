<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\QueryGroup\Option as SolrOption;
use JsonSerializable;
use Netlogix\Nxsolrajax\Traits\FacetUrlTrait;

class Option extends SolrOption implements JsonSerializable
{
    use FacetUrlTrait;

    public function jsonSerialize(): array
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

    public function getUrl(): string
    {
        return $this->getFacetItemUrl($this);
    }
}
