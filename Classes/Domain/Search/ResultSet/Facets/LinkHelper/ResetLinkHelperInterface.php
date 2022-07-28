<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\LinkHelper;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacet;

/**
 * @deprecated use PSR-14 events
 */
interface ResetLinkHelperInterface
{
    /**
     * @param AbstractFacet $facet
     * @return bool
     * @deprecated replace this with GenerateFacetResetUrlEvent
     */
    public function canHandleResetLink(AbstractFacet $facet): bool;

    /**
     * @param AbstractFacet $facet
     * @return string
     * @deprecated replace this with GenerateFacetResetUrlEvent
     */
    public function renderResetLink(AbstractFacet $facet): string;
}
