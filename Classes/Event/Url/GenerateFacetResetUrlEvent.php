<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Event\Url;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacet;

final class GenerateFacetResetUrlEvent
{

    private AbstractFacet $facet;
    private string $url;

    public function __construct(AbstractFacet $facet, string $url)
    {
        $this->facet = $facet;
        $this->url = $url;
    }

    public function getFacet(): AbstractFacet
    {
        return $this->facet;
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
}