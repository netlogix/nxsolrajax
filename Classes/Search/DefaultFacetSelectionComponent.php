<?php

namespace Netlogix\Nxsolrajax\Search;

use ApacheSolrForTypo3\Solr\Search\AbstractComponent;
use Netlogix\Nxsolrajax\Query\Modifier\DefaultFacetSelection;

class DefaultFacetSelectionComponent extends AbstractComponent
{
    public function initializeSearchComponent()
    {
        if (isset($this->searchConfiguration['faceting']) && !empty($this->searchConfiguration['faceting'])) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['modifySearchQuery']['defaultFacetSelection'] = DefaultFacetSelection::class;
        }
    }
}
