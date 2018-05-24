<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\LinkHelper;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\AbstractFacet;

interface ResetLinkHelperInterface
{
    public function canHandleResetLink(AbstractFacet $facet): bool;

    public function renderResetLink(AbstractFacet $facet): string;
}
