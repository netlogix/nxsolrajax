<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Event\Url;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacetItem;

final class GenerateFacetItemUrlEvent
{

    private string $url = '';
    private AbstractFacetItem $facetItem;
    private string $overrideUriValue;

    public function __construct(AbstractFacetItem $facetItem, string $url, string $overrideUriValue = '')
    {
        $this->url = $url;
        $this->facetItem = $facetItem;
        $this->overrideUriValue = $overrideUriValue;
    }


    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return AbstractFacetItem
     */
    public function getFacetItem(): AbstractFacetItem
    {
        return $this->facetItem;
    }

    /**
     * @return string
     */
    public function getOverrideUriValue(): string
    {
        return $this->overrideUriValue;
    }
}