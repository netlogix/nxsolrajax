<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\Node as SolrNode;
use JsonSerializable;
use Netlogix\Nxsolrajax\Traits\FacetUrlTrait;

class Node extends SolrNode implements JsonSerializable
{
    use FacetUrlTrait;

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getKey(),
            'label' => $this->getLabel(),
            'name' => $this->getValue(),
            'count' => $this->getDocumentCount(),
            'selected' => $this->getSelected(),
            'active' => $this->isActive(),
            'options' => $this->addSearchUriBuilderToOptions(array_values($this->childNodes->getArrayCopy())),
            'links' => [
                'self' => $this->getUrl(),
            ],
        ];
    }

    public function isActive(): bool
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

    public function getUrl(): string
    {
        return $this->getFacetItemUrl($this);
    }
}
