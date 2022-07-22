<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\LinkHelper;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacetItem;

/**
 * @deprecated use PSR-14 events
 */
interface SelfLinkHelperInterface
{
    /**
     * @deprecated replace this with GenerateFacetItemUrlEvent
     * @param AbstractFacetItem $facetItem
     * @return bool
     */
    public function canHandleSelfLink(AbstractFacetItem $facetItem): bool;

    /**
     * @deprecated replace this with GenerateFacetItemUrlEvent
     * @param AbstractFacetItem $facetItem
     * @return string
     */
    public function renderSelfLink(AbstractFacetItem $facetItem): string;
}
