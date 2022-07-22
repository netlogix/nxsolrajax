<?php

namespace Netlogix\Nxsolrajax;

/*
 * This file is part of the Netlogix.Nxsolrajax package.
 */

use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;

/**
 * @deprecated this is deprecated in favour of AfterGetSuggestionsEvent
 */
interface SugesstionResultModifier
{

    /**
     * @deprecated this is deprecated in favour of AfterGetSuggestionsEvent
     * @param string $query
     * @param array $suggestions
     * @param TypoScriptConfiguration $typoScriptConfiguration
     * @return array
     */
    public function modifySuggestions($query, array $suggestions, TypoScriptConfiguration $typoScriptConfiguration);
}
