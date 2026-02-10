<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Options;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Options\OptionsFacet as SolrOptionsFacet;
use JsonSerializable;
use Netlogix\Nxsolrajax\Traits\FacetUrlTrait;

class OptionsFacet extends SolrOptionsFacet implements JsonSerializable
{
    use FacetUrlTrait;

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->getName(),
            'type' => self::TYPE_OPTIONS,
            'label' => $this->getLabel(),
            'used' => $this->getIsUsed(),
            'options' => $this->addSearchUriBuilderToOptions(array_values($this->getOptions()->getArrayCopy())),
            'links' => [
                'reset' => $this->getResetUrl(),
            ],
        ];
    }

    public function getResetUrl(): string
    {
        return $this->getFacetResetUrl($this);
    }
}
