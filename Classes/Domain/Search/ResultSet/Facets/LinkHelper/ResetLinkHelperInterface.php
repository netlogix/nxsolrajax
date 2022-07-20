<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\LinkHelper;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacet;

/**
 * @deprecated use PSR-14 events
 */
interface ResetLinkHelperInterface
{
    /**
     * @deprecated replace this with GenerateFacetResetUrlEvent
     * @param AbstractFacet $facet
     * @return bool
     */
    public function canHandleResetLink(AbstractFacet $facet): bool;

    /**
     * @deprecated replace this with GenerateFacetResetUrlEvent
     * @param AbstractFacet $facet
     * @return string
     */
    public function renderResetLink(AbstractFacet $facet): string;
}
