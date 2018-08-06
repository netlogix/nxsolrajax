<?php

namespace Netlogix\Nxsolrajax;

/*
 * This file is part of the Netlogix.Nxsolrajax package.
 */

use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;

interface SugesstionResultModifier
{

    /**
     * @param string $query
     * @param array $suggestions
     * @param TypoScriptConfiguration $typoScriptConfiguration
     * @return array
     */
    public function modifySuggestions($query, array $suggestions, TypoScriptConfiguration $typoScriptConfiguration);
}
