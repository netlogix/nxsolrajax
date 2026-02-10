<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Event\Url;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacet;

final class GenerateFacetResetUrlEvent
{
    public function __construct(public readonly AbstractFacet $facet, private string $url) {}

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }
}
