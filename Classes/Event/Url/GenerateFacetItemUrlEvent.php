<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Event\Url;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacetItem;

final class GenerateFacetItemUrlEvent
{

    public function __construct(
        public readonly AbstractFacetItem $facetItem,
        private string $url,
        public readonly string $overrideUriValue = ''
    ) {
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }
}
