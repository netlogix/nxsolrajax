<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\LinkHelper;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacetItem;

interface SelfLinkHelperInterface
{
    public function canHandleSelfLink(AbstractFacetItem $facetItem): bool;

    public function renderSelfLink(AbstractFacetItem $facetItem): string;
}
