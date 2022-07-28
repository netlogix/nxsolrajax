<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\LinkHelper;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacetItem;

/**
 * @deprecated use PSR-14 events
 */
interface SelfLinkHelperInterface
{
    /**
     * @param AbstractFacetItem $facetItem
     * @return bool
     * @deprecated replace this with GenerateFacetItemUrlEvent
     */
    public function canHandleSelfLink(AbstractFacetItem $facetItem): bool;

    /**
     * @param AbstractFacetItem $facetItem
     * @return string
     * @deprecated replace this with GenerateFacetItemUrlEvent
     */
    public function renderSelfLink(AbstractFacetItem $facetItem): string;
}
