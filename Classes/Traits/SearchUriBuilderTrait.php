<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Traits;

use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;

trait SearchUriBuilderTrait
{

    protected SearchUriBuilder $searchUriBuilder;

    public function setSearchUriBuilder(SearchUriBuilder $searchUriBuilder): void
    {
        $this->searchUriBuilder = $searchUriBuilder;
    }

}
