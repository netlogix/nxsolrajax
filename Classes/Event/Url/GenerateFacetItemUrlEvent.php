<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Event\Url;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacetItem;

final class GenerateFacetItemUrlEvent
{

    private string $url;
    private AbstractFacetItem $facetItem;
    private string $overrideUriValue;

    public function __construct(AbstractFacetItem $facetItem, string $url, string $overrideUriValue = '')
    {
        $this->url = $url;
        $this->facetItem = $facetItem;
        $this->overrideUriValue = $overrideUriValue;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getFacetItem(): AbstractFacetItem
    {
        return $this->facetItem;
    }

    public function getOverrideUriValue(): string
    {
        return $this->overrideUriValue;
    }
}